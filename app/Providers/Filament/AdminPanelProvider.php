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
                /*
                 * The brown off the shop's own cards, so the admin and the
                 * storefront are recognisably the same business.
                 *
                 * Written out shade by shade rather than handed to
                 * Color::hex(), which keeps only the hue of what it is given
                 * and applies its own chroma — and brown is a dark, washed-out
                 * orange, so what came back was orange. The 500 below is
                 * #8a6c50 exactly; white on the 600 that fills a button is
                 * 6.6 to 1.
                 */
                'primary' => [
                    50  => 'oklch(0.975 0.010 64.3)',
                    100 => 'oklch(0.945 0.018 64.3)',
                    200 => 'oklch(0.895 0.030 64.3)',
                    300 => 'oklch(0.825 0.042 64.3)',
                    400 => 'oklch(0.700 0.052 64.3)',
                    500 => 'oklch(0.554 0.056 64.3)',
                    600 => 'oklch(0.480 0.052 64.3)',
                    700 => 'oklch(0.405 0.046 64.3)',
                    800 => 'oklch(0.330 0.038 64.3)',
                    900 => 'oklch(0.270 0.030 64.3)',
                    950 => 'oklch(0.190 0.022 64.3)',
                ],
                // A warm neutral rather than a blue-grey one: every surface in
                // here sits next to that brown, and a cold grey beside it
                // reads as a different piece of software.
                'gray'    => Color::Taupe,
                'danger'  => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            /*
             * And the paper itself. Filament's own greys stop at a warm white;
             * the shop's cards are cream, and the two sitting side by side on
             * one screen look like a mistake rather than a choice. Written as
             * a style block rather than a stylesheet because a stylesheet has
             * to be published by composer, and this shop deploys with git pull.
             */
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.paper')->render(),
            )
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
