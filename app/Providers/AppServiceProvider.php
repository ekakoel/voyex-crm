<?php

namespace App\Providers;

use App\Http\View\IndexStatsComposer;
use App\Http\View\SidebarComposer;
use App\Http\View\CompanyBrandComposer;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $helpers = app_path('Support/helpers.php');
        if (is_file($helpers)) {
            require_once $helpers;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.master', SidebarComposer::class);
        View::composer('modules.*.index', IndexStatsComposer::class);
        View::composer([
            'auth.login',
            'auth.forgot-password',
            'auth.reset-password',
        ], CompanyBrandComposer::class);
    }
}
