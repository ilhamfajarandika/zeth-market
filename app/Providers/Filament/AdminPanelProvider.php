<?php

namespace App\Providers\Filament;

use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path(config('app.admin_path'))
            ->login()
            ->brandName(config('app.name'))
            ->brandLogo(fn () => view('filament.brand-logo'))
            ->brandLogoHeight('2rem')
            ->favicon(fn (): string => asset('favicon-32x32.png'))
            ->defaultThemeMode(ThemeMode::Dark)
            // Palet dari --color-brand, --color-ground, dan turunannya di resources/css/app.css (landing page).
            ->colors([
                'primary' => [
                    50 => '#ffeefc',
                    100 => '#ffd9f8',
                    200 => '#ffb5f1',
                    300 => '#ff75e4',
                    400 => '#ff2bd6',
                    500 => '#ff2bd6',
                    600 => '#e8174a',
                    700 => '#b80f38',
                    800 => '#7b0c2a',
                    900 => '#4f091f',
                    950 => '#280716',
                ],
                'gray' => [
                    50 => '#f4f6fb',
                    100 => '#e2e5ee',
                    200 => '#d0d5e0',
                    300 => '#bec4d3',
                    400 => '#9aa3b8',
                    500 => '#767792',
                    600 => '#5e5a79',
                    700 => '#453c60',
                    800 => '#21103a',
                    900 => '#140a22',
                    950 => '#09050f',
                ],
            ])
            ->font('Instrument Sans')
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->navigationGroups(['Transaksi', 'Katalog', 'Pengaturan'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
