<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Native\Mobile\Edge\Components\Navigation\BottomNav;
use Native\Mobile\Edge\Components\Navigation\BottomNavItem;
use Native\Mobile\Edge\Components\Navigation\TopBar;
use Native\Mobile\Edge\Components\Navigation\TopBarAction;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Blade::component('native-top-bar', TopBar::class);
        Blade::component('native-top-bar-action', TopBarAction::class);
        Blade::component('native-bottom-nav', BottomNav::class);
        Blade::component('native-bottom-nav-item', BottomNavItem::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch();
    }
}
