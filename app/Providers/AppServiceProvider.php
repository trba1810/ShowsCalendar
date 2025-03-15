<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TmdbService;
use App\Services\TvMazeService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TmdbService::class, function () {
            return new TmdbService();
        });
        $this->app->singleton(TvMazeService::class, function ($app) {
            return new TvMazeService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
