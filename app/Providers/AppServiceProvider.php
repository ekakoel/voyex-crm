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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.master', SidebarComposer::class);
        View::composer('modules.*.index', IndexStatsComposer::class);
        View::composer('auth.login', CompanyBrandComposer::class);
    }
}
