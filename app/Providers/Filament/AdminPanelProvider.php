<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\WebOrders\WebOrderResource;
use App\Filament\Widgets\LatestSales;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\StockAlerts;
use App\Filament\Widgets\TopProducts;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->brandName('Punto Manija')
            ->brandLogo(asset('images/punto-manija-logo.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/punto-manija-mascot.png'))
            ->colors([
                'primary' => Color::Pink,
            ])
            ->homeUrl(function (): string {
                $user = Auth::user();

                if ($user instanceof User && $user->isDelivery()) {
                    return WebOrderResource::getUrl();
                }

                return Dashboard::getUrl();
            })
            ->databaseNotifications()
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.partials.print-agent-listener')->render(),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                StatsOverview::class,
                LatestSales::class,
                TopProducts::class,
                StockAlerts::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
