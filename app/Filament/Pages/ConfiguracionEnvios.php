<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\Shipping\ShippingPriceCalculator;
use App\Services\Shipping\ShippingSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ConfiguracionEnvios extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $navigationLabel = 'Tarifas de envío';

    protected static ?string $title = 'Tarifas de envío';

    protected static ?string $slug = 'tarifas-envio';

    protected static string|UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.configuracion-envios';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getSubheading(): ?string
    {
        return 'Definí cómo se cotiza el envío a domicilio en el marketplace.';
    }

    public function mount(): void
    {
        $this->form->fill(ShippingSettings::all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Callout::make('Cómo se calcula')
                    ->info()
                    ->description('Precio = base + (km × precio por km), redondeado al múltiplo configurado. Si la distancia supera el máximo, el cliente coordina por WhatsApp.'),

                Grid::make([
                    'default' => 1,
                    'lg' => 5,
                ])->schema([
                    Section::make('Parámetros')
                        ->description('Estos valores se usan al cotizar envíos en la tienda pública.')
                        ->icon(Heroicon::OutlinedCalculator)
                        ->columnSpan(['lg' => 3])
                        ->columns(2)
                        ->schema([
                            TextInput::make('base_price')
                                ->label('Precio base')
                                ->prefix('$')
                                ->numeric()
                                ->minValue(0)
                                ->step(50)
                                ->required()
                                ->live(onBlur: true)
                                ->helperText('Monto fijo que se suma a toda cotización.'),
                            TextInput::make('price_per_km')
                                ->label('Precio por km')
                                ->prefix('$')
                                ->numeric()
                                ->minValue(0)
                                ->step(50)
                                ->required()
                                ->live(onBlur: true)
                                ->helperText('Se multiplica por la distancia real en km.'),
                            TextInput::make('max_distance_km')
                                ->label('Distancia máxima')
                                ->suffix('km')
                                ->numeric()
                                ->minValue(0.1)
                                ->step(0.5)
                                ->required()
                                ->live(onBlur: true)
                                ->helperText('Más allá de este radio no se cotiza automático.'),
                            TextInput::make('rounding_step')
                                ->label('Redondeo')
                                ->prefix('$')
                                ->numeric()
                                ->minValue(1)
                                ->step(1)
                                ->required()
                                ->live(onBlur: true)
                                ->helperText('Ej.: 50 redondea a múltiplos de $50.'),
                        ]),

                    Section::make('Vista previa')
                        ->description('Se actualiza al salir de cada campo.')
                        ->icon(Heroicon::OutlinedEye)
                        ->columnSpan(['lg' => 2])
                        ->schema([
                            View::make('filament.pages.partials.shipping-quote-preview')
                                ->viewData(fn (Get $get): array => [
                                    'samples' => $this->buildPreviewSamples($get),
                                ]),
                        ]),
                ]),
            ])
            ->statePath('data');
    }

    /**
     * @return list<array{km: float, label: string, cost: float|null, out_of_range: bool, is_boundary: bool}>
     */
    protected function buildPreviewSamples(Get $get): array
    {
        $settings = [
            'base_price' => (float) ($get('base_price') ?? 0),
            'price_per_km' => (float) ($get('price_per_km') ?? 0),
            'max_distance_km' => (float) ($get('max_distance_km') ?? 0),
            'rounding_step' => (float) ($get('rounding_step') ?? 1),
        ];

        $max = $settings['max_distance_km'];
        $calculator = app(ShippingPriceCalculator::class);

        $distances = collect([1.0, 3.0, 5.0, 10.0])
            ->filter(fn (float $km): bool => $km > 0 && ($max <= 0 || $km < $max))
            ->when($max > 0, fn ($c) => $c->push($max))
            ->unique()
            ->sort()
            ->values();

        if ($max > 0) {
            $distances->push(round($max + 1, 1));
        }

        return $distances
            ->map(function (float $km) use ($calculator, $settings, $max): array {
                $quote = $calculator->quote($km, $settings);
                $isBoundary = $max > 0 && abs($km - $max) < 0.001;

                return [
                    'km' => $km,
                    'label' => $isBoundary
                        ? number_format($km, $km == floor($km) ? 0 : 1, ',', '.').' km (máx.)'
                        : number_format($km, $km == floor($km) ? 0 : 1, ',', '.').' km',
                    'cost' => $quote['cost'],
                    'out_of_range' => $quote['out_of_range'],
                    'is_boundary' => $isBoundary,
                ];
            })
            ->all();
    }

    protected function getForms(): array
    {
        return ['form'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar cambios')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        ShippingSettings::save($data);

        Notification::make()
            ->title('Tarifas de envío actualizadas')
            ->body('Los cambios ya aplican en el marketplace.')
            ->success()
            ->send();
    }
}
