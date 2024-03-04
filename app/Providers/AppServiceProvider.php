<?php

namespace App\Providers;

use BenBjurstrom\Replicate\Replicate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Replicate::class, function () {
            return new Replicate(
                apiToken: config('services.replicate.api_token'),
            );
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
