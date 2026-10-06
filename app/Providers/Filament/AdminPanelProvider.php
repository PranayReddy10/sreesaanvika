<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Support\InitialsAvatarProvider;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->path('admin')
            ->login()
            ->brandName('OJASVI')
            ->favicon(asset('brand/favicon.png'))
            ->colors([
                // The shop's own gold, so the admin and the storefront are
                // recognisably the same business.
                'primary' => Color::hex('#a8781f'),
                'danger'  => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            // Filament asks ui-avatars.com by default; this draws it here.
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->navigationGroups([
                'Catalogue',
                'Selling',
                'Storefront',
                'Shop',
            ])
            /*
             * No notification bell. Nothing in the shop sends a stored
             * notification — every message raised in the admin is a flash one,
             * shown and gone — so the bell would always be empty, and Filament
             * would ask the server every 30 seconds whether it still is.
             * Requests are the scarce thing on shared hosting.
             */
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                \App\Filament\Widgets\ShopOverview::class,
                \App\Filament\Widgets\SalesChart::class,
                \App\Filament\Widgets\OrdersNeedingWork::class,
                \App\Filament\Widgets\RunningLow::class,
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
