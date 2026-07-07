<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessImportFile;
use App\Models\ProductImport;
use App\Models\Supplier;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class CargarProductos extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Cargar Productos';

    protected static ?string $title = 'Cargar productos';

    protected static ?string $slug = 'cargar-productos';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.cargar-productos';

    public $importFile = null;

    public ?int $importSupplierId = null;

    public ?string $supplierAutoDetectedName = null;

    public ?string $originalFilename = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageProducts();
    }

    public bool $hasDuplicate = false;

    public ?int $duplicateImportId = null;

    public bool $forceReprocess = false;

    public static function getNavigationBadge(): ?string
    {
        $count = ProductImport::where('user_id', Auth::id())->where('status', 'done')->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function mount(): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            ProductImport::dismissResolvedNotificationsFor($user);
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                FileUpload::make('importFile')
                    ->label('Archivo')
                    ->disk('local')
                    ->directory('imports')
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                    ->maxSize(25600)
                    ->required()
                    ->helperText('PDF, XLSX o XLS — máximo 25 MB.'),
                Select::make('importSupplierId')
                    ->label('Proveedor (opcional)')
                    ->options(fn () => Supplier::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required(),
                    ])
                    ->createOptionUsing(fn (array $data) => Supplier::create($data)->getKey()),
            ]);
    }

    protected function getForms(): array
    {
        return ['form'];
    }

    #[Computed]
    public function pendingImports()
    {
        return ProductImport::where('user_id', Auth::id())
            ->where('status', 'done')
            ->latest()
            ->get();
    }

    #[Computed]
    public function processingImports()
    {
        return ProductImport::where('user_id', Auth::id())
            ->whereIn('status', ['pending', 'processing'])
            ->latest()
            ->get();
    }

    private function resolveUploadedFile(): ?TemporaryUploadedFile
    {
        $file = $this->importFile;

        if (is_array($file)) {
            $file = reset($file) ?: null;
        }

        return $file instanceof TemporaryUploadedFile ? $file : null;
    }

    public function updatedImportFile(): void
    {
        $this->hasDuplicate = false;
        $this->duplicateImportId = null;
        $this->forceReprocess = false;
        $this->supplierAutoDetectedName = null;
        $this->originalFilename = null;

        $file = $this->resolveUploadedFile();

        if (! $file) {
            return;
        }

        $this->originalFilename = $file->getClientOriginalName();

        $hash = hash_file('sha256', $file->getRealPath());

        $duplicate = ProductImport::where('user_id', Auth::id())
            ->where('file_hash', $hash)
            ->where('status', '!=', 'error')
            ->latest()
            ->first();

        if ($duplicate) {
            $this->hasDuplicate = true;
            $this->duplicateImportId = $duplicate->id;
        }

        $this->detectSupplierFromFilename($this->originalFilename);
    }

    private function detectSupplierFromFilename(string $filename): void
    {
        $normalizedFilename = Str::lower(preg_replace('/[_\-.]+/', ' ', Str::ascii($filename)));

        foreach (Supplier::orderBy('name')->pluck('name', 'id') as $id => $name) {
            $normalizedName = Str::lower(preg_replace('/[_\-.]+/', ' ', Str::ascii($name)));

            if ($normalizedName !== '' && str_contains($normalizedFilename, $normalizedName)) {
                $this->importSupplierId = $id;
                $this->supplierAutoDetectedName = $name;

                return;
            }
        }
    }

    public function submitImport(): void
    {
        $state = $this->form->getState();
        $storedPath = $state['importFile'] ?? null;

        if (! $storedPath) {
            Notification::make()
                ->title('Seleccioná un archivo PDF o Excel.')
                ->warning()
                ->send();

            return;
        }

        $this->resetErrorBag();

        $hash = hash_file('sha256', Storage::disk('local')->path($storedPath));

        $duplicate = ProductImport::where('user_id', Auth::id())
            ->where('file_hash', $hash)
            ->where('status', '!=', 'error')
            ->latest()
            ->first();

        if ($duplicate && ! $this->forceReprocess) {
            $this->hasDuplicate = true;
            $this->duplicateImportId = $duplicate->id;

            return;
        }

        $import = ProductImport::create([
            'user_id' => Auth::id(),
            'supplier_id' => $this->importSupplierId,
            'filename' => $this->originalFilename,
            'file_path' => $storedPath,
            'file_hash' => $hash,
            'status' => 'pending',
        ]);

        ProcessImportFile::dispatch($import->id);

        $this->reset(['importFile', 'importSupplierId', 'supplierAutoDetectedName', 'originalFilename', 'hasDuplicate', 'duplicateImportId', 'forceReprocess']);
        $this->form->fill();
        unset($this->pendingImports);

        Notification::make()
            ->title('Archivo encolado')
            ->body('Te avisamos cuando esté listo para revisar.')
            ->success()
            ->send();
    }

    public function cancelDuplicate(): void
    {
        $this->reset(['importFile', 'originalFilename', 'hasDuplicate', 'duplicateImportId', 'forceReprocess']);
        $this->form->fill();
    }

    public function processAnyway(): void
    {
        $this->forceReprocess = true;
        $this->submitImport();
    }
}
