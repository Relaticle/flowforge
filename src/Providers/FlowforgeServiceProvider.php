<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Providers;

use Illuminate\Support\ServiceProvider;
use Relaticle\Flowforge\Support\EloquentSerializer;

class FlowforgeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('eloquent.serialize', function () {
            return new EloquentSerializer;
        });

        $this->app->alias('eloquent.serialize', EloquentSerializer::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register any package views, assets, routes, etc. here if needed
    }
}
