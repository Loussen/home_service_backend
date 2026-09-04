<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetAdminLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
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
            ->authGuard('admin')
            ->authPasswordBroker('admins')
            ->brandName('My Sancho')
            ->brandLogo(fn (): HtmlString => $this->brandLogoHtml())
            ->brandLogoHeight('3.5rem')
            ->favicon(asset('images/brand/logo-light.jpg'))
            ->colors([
                'primary' => Color::hex('#08215B'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                \App\Filament\Widgets\PendingProviderApprovalsWidget::class,
                AccountWidget::class,
            ])
            ->databaseNotifications()
            ->middleware([
                SetAdminLocale::class,
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

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn (): string => '<style>
                .fi-logo {
                    overflow: visible !important;
                    display: flex !important;
                    align-items: center !important;
                }
                .fi-sidebar-header,
                .fi-topbar-start {
                    align-items: center !important;
                }
                .fi-simple-header .fi-logo { height: 7.25rem !important; }
                .ms-admin-brand {
                    display: inline-flex;
                    align-items: center;
                    justify-content: flex-start;
                    gap: 0.75rem;
                    height: 100%;
                    max-width: 100%;
                }
                .ms-admin-brand img {
                    height: 100%;
                    width: auto;
                    border-radius: 0.4rem;
                    display: block;
                    flex-shrink: 0;
                }
                .ms-admin-brand-label {
                    display: inline-flex;
                    align-items: center;
                    color: #08215B;
                    font-size: 0.95rem;
                    font-weight: 700;
                    line-height: 1.15;
                    white-space: nowrap;
                    margin: 0;
                    padding: 0;
                    align-self: center;
                }
                .fi-sidebar-header .ms-admin-brand-label {
                    font-size: 0.88rem;
                }
                @media (max-width: 640px) {
                    .fi-topbar .ms-admin-brand-label { display: none; }
                }
            </style>',
        );
    }

    private function brandLogoHtml(): HtmlString
    {
        $src = e(asset('images/brand/logo-color.jpg'));
        $isAuthPage = request()->routeIs('filament.admin.auth.*');

        $label = $isAuthPage
            ? ''
            : '<span class="ms-admin-brand-label">My Sancho — Admin Panel</span>';

        return new HtmlString(
            '<span class="ms-admin-brand">'
            .'<img src="'.$src.'" alt="My Sancho" />'
            .$label
            .'</span>'
        );
    }
}
