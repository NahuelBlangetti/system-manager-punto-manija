<?php

namespace App\Filament\Pages;

use App\Models\PointPayment;
use App\Models\Setting;
use App\Models\User;
use App\Services\MercadoPago\MercadoPagoException;
use App\Services\MercadoPago\PointClient;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use UnitEnum;

class ConfiguracionPosnet extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDeviceTablet;

    protected static ?string $navigationLabel = 'Posnet Mercado Pago';

    protected static ?string $title = 'Posnet Mercado Pago';

    protected static ?string $slug = 'posnet-mercado-pago';

    protected static string|UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.configuracion-posnet';

    /** @var array<int, array<string, mixed>> */
    public array $terminals = [];

    public bool $terminalsLoaded = false;

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
        return 'Vinculá el posnet Point (N950) para cobrar las ventas del POS con tarjeta, contactless o QR.';
    }

    #[Computed]
    public function isConfigured(): bool
    {
        return app(PointClient::class)->isConfigured();
    }

    #[Computed]
    public function currentTerminalId(): ?string
    {
        return app(PointClient::class)->terminalId();
    }

    #[Computed]
    public function webhookUrl(): string
    {
        return route('webhooks.mercadopago');
    }

    #[Computed]
    public function hasWebhookSecret(): bool
    {
        return filled(config('services.mercadopago.webhook_secret'));
    }

    /**
     * @return Collection<int, PointPayment>
     */
    #[Computed]
    public function recentPayments(): Collection
    {
        return PointPayment::with(['sale', 'user'])->latest()->limit(20)->get();
    }

    public function loadTerminals(): void
    {
        try {
            $this->terminals = app(PointClient::class)->listTerminals();
            $this->terminalsLoaded = true;
        } catch (MercadoPagoException $e) {
            Notification::make()->title('No se pudieron listar los posnets')->body($e->getMessage())->danger()->send();

            return;
        }

        if (empty($this->terminals)) {
            Notification::make()
                ->title('No hay posnets vinculados')
                ->body('Vinculá el N950 a la cuenta desde la app de Mercado Pago (Tu negocio → Point) y volvé a buscar.')
                ->warning()
                ->send();
        }
    }

    public function useTerminal(string $terminalId): void
    {
        Setting::set(PointClient::TERMINAL_SETTING_KEY, $terminalId);
        unset($this->currentTerminalId);

        Notification::make()->title('Posnet seleccionado')->body($terminalId)->success()->send();
    }

    public function activatePdvMode(string $terminalId): void
    {
        try {
            app(PointClient::class)->setOperatingMode($terminalId, 'PDV');
        } catch (MercadoPagoException $e) {
            Notification::make()->title('No se pudo activar el modo PDV')->body($e->getMessage())->danger()->send();

            return;
        }

        Notification::make()
            ->title('Modo PDV activado')
            ->body('Reiniciá el posnet para que tome el cambio. Desde ahora recibe los cobros del sistema.')
            ->success()
            ->persistent()
            ->send();

        $this->loadTerminals();
    }
}
