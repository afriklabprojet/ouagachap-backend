<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SecurityHeaders;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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
            
            // 🎨 Branding OUAGA CHAP
            ->brandName('OUAGA CHAP')
            ->brandLogo(asset('images/logo.svg'))
            ->darkModeBrandLogo(asset('images/logo-dark.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/favicon.png'))
            
            // 🎨 Couleurs Orange/Vert (couleurs du Burkina Faso)
            ->colors([
                'primary' => Color::Orange,
                'success' => Color::Emerald,
                'danger' => Color::Red,
                'warning' => Color::Amber,
                'info' => Color::Sky,
                'gray' => Color::Slate,
            ])
            
            // 🌙 Mode sombre
            ->darkMode(true)
            
            // 📁 Navigation groupée
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Opérations')
                    ->icon('heroicon-o-truck'),
                NavigationGroup::make()
                    ->label('Utilisateurs')
                    ->icon('heroicon-o-users'),
                NavigationGroup::make()
                    ->label('Finance')
                    ->icon('heroicon-o-banknotes'),
                NavigationGroup::make()
                    ->label('Configuration')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Système')
                    ->icon('heroicon-o-server')
                    ->collapsed(),
            ])
            
            // 🔍 Recherche globale
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            
            // 📱 Sidebar collapsible
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            
            // 🔐 Sécurité
            ->authGuard('web')
            ->passwordReset()
            ->profile()
            ->spa(false)
            
            // 📄 Pages & Resources
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Dashboard personnalisé
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Pas de widgets par défaut - on utilise les nôtres
            ])
            
            // 🔒 Middleware
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
                SecurityHeaders::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            
            // 📐 Layout
            ->maxContentWidth('full')
            
            // 🔔 Notifications
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s');
    }
}
