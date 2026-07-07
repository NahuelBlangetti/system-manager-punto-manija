<?php

namespace App\Filament\Pages;

use App\Filament\Support\ProductUnitNormalizer;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImport;
use App\Models\Supplier;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;

class ValidarImport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Validar importación';

    protected static ?string $slug = 'validar-import';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.validar-import';

    public ?ProductImport $import = null;

    public array $products = [];

    public ?int $importSupplierId = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageProducts();
    }

    public function mount(): void
    {
        $id = request()->query('id');
        $import = $id ? ProductImport::find($id) : null;

        if (! $import || $import->user_id !== Auth::id()) {
            $this->rejectImport('Importación no encontrada', 'No se encontró la importación solicitada.');

            return;
        }

        if (! $import->isReviewable()) {
            [$title, $body] = match ($import->status) {
                'processing', 'pending' => [
                    'Importación en curso',
                    'El archivo todavía se está analizando. Cuando termine, aparecerá en "Listas para revisar" y te llegará una notificación.',
                ],
                'cancelled' => [
                    'Importación cancelada',
                    'Esta importación fue cancelada. Subí el archivo nuevamente si querés importarlo.',
                ],
                'validated' => [
                    'Importación ya confirmada',
                    'Ya guardaste los productos de este archivo en el catálogo.',
                ],
                'error' => [
                    'Error al procesar',
                    $import->error_message ?? 'Hubo un error al analizar el archivo. Intentá subirlo de nuevo.',
                ],
                default => [
                    'Importación no disponible',
                    'Esta importación ya no se puede revisar.',
                ],
            };

            $this->rejectImport($title, $body);

            return;
        }

        // Reabrir una importación cancelada que aún tiene productos.
        if ($import->isCancelled()) {
            $import->update(['status' => 'done']);
            $import->refresh();
        }

        $this->import = $import;
        $this->products = $import->products ?? [];
        $this->form->fill(['importSupplierId' => $import->supplier_id]);
    }

    private function rejectImport(string $title, string $body): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->warning()
            ->send();

        $this->redirect(CargarProductos::getUrl());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('importSupplierId')
                    ->label('Proveedor')
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
    public function categoryOptions(): array
    {
        return Category::orderBy('name')->pluck('name', 'id')->toArray();
    }

    #[Computed]
    public function unitOptions(): array
    {
        return array_combine(ProductUnitNormalizer::WHITELIST, ProductUnitNormalizer::WHITELIST);
    }

    #[Computed]
    public function stats(): array
    {
        $new = $update = $duplicate = $selected = 0;

        foreach ($this->products as $product) {
            if (! empty($product['selected'])) {
                $selected++;
            }

            if (! empty($product['internal_duplicate'])) {
                $duplicate++;
            } elseif (($product['action'] ?? '') === 'update') {
                $update++;
            } else {
                $new++;
            }
        }

        return [
            'total' => count($this->products),
            'new' => $new,
            'update' => $update,
            'duplicate' => $duplicate,
            'selected' => $selected,
        ];
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function removeProduct(int $index): void
    {
        unset($this->products[$index]);
        $this->products = array_values($this->products);
    }

    public function toggleAll(bool $checked): void
    {
        foreach ($this->products as $i => $_) {
            $this->products[$i]['selected'] = $checked;
        }
    }

    public function createProducts(): void
    {
        $selected = array_values(array_filter($this->products, fn ($p) => $p['selected'] && trim($p['name']) !== ''));

        if (empty($selected)) {
            Notification::make()
                ->title('Ningún producto seleccionado')
                ->body('Marcá al menos un producto para guardar.')
                ->warning()
                ->send();

            return;
        }

        $created = 0;
        $updated = 0;
        $now = now();

        DB::transaction(function () use ($selected, $now, &$created, &$updated) {
            $createRows = [];

            foreach ($selected as $row) {
                if ($row['action'] === 'update' && ! empty($row['existing_product_id'])) {
                    Product::where('id', $row['existing_product_id'])->update([
                        'name' => $row['name'],
                        'sku' => $row['sku'] ?: null,
                        'barcode' => $row['barcode'] ?: null,
                        'unit' => $row['unit'],
                        'cost_price' => $row['cost_price'],
                        'sale_price' => $row['sale_price'],
                        'stock' => (int) $row['stock'],
                        'category_id' => $row['category_id'] ?: null,
                        'supplier_id' => $this->importSupplierId,
                        'updated_at' => $now,
                    ]);
                    $updated++;

                    continue;
                }

                $createRows[] = [
                    'name' => $row['name'],
                    'sku' => $row['sku'] ?: null,
                    'barcode' => $row['barcode'] ?: null,
                    'unit' => $row['unit'],
                    'cost_price' => $row['cost_price'],
                    'sale_price' => $row['sale_price'],
                    'stock' => (int) $row['stock'],
                    'min_stock' => 0,
                    'category_id' => $row['category_id'] ?: null,
                    'supplier_id' => $this->importSupplierId,
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($createRows, 200) as $chunk) {
                DB::table('products')->insert($chunk);
            }

            $created = count($createRows);
        });

        $this->import->update(['status' => 'validated']);
        $this->import->dismissReviewNotifications();

        Notification::make()
            ->title("{$created} creado(s), {$updated} actualizado(s)")
            ->success()
            ->send();

        $this->redirect(CargarProductos::getUrl());
    }

    public function cancelImport(): void
    {
        if (! $this->import || $this->import->status !== 'done') {
            return;
        }

        if ($this->import->file_path && Storage::disk('local')->exists($this->import->file_path)) {
            Storage::disk('local')->delete($this->import->file_path);
        }

        $this->import->update([
            'status' => 'cancelled',
        ]);

        $this->import->dismissReviewNotifications();

        Notification::make()
            ->title('Importación cancelada')
            ->body('No se guardó ningún producto.')
            ->success()
            ->send();

        $this->redirect(CargarProductos::getUrl());
    }
}
