<?php

namespace Relaticle\Flowforge;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Livewire\Livewire;

/**
 * Filament Panel plugin for FlowForge.
 *
 * This class requires the full `filament/filament` package (Panel Builder).
 * For standalone Livewire usage without a panel, use the InteractsWithBoard
 * trait directly on your Livewire component instead.
 *
 * @see \Relaticle\Flowforge\Concerns\InteractsWithBoard
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
        // Livewire::component('relaticle.flowforge.livewire.kanban-board', KanbanBoard::class);
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
