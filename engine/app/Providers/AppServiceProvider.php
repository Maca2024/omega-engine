<?php

/**
 * ==============================================================================
 * APP SERVICE PROVIDER
 * ==============================================================================
 *
 * De centrale service provider voor de Omega Engine.
 */

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * De Application Service Provider.
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Services worden later geregistreerd wanneer nodig
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bootstrap logic hier
    }
}
