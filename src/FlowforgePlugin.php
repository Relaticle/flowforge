<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Relaticle\Flowforge\Concerns\InteractsWithBoard;

/**
 * Filament Panel plugin for FlowForge.
 *
 * This class requires the full `filament/filament` package (Panel Builder).
 * For standalone Livewire usage without a panel, use the InteractsWithBoard
 * trait directly on your Livewire component instead.
 *
 * @see InteractsWithBoard
 */
class FlowforgePlugin implements Plugin
{
    public function getId(): string
    {
        return 'flowforge';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
