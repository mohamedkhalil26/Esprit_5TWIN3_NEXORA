<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

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
        // Les deux templates sont basés sur Bootstrap 5
        Paginator::useBootstrapFive();

        // Dates en français (ex. "4 octobre 2026", "il y a 2 jours")
        Carbon::setLocale(config('app.locale'));
    }
}
