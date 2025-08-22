<?php

namespace Relaticle\Flowforge;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Livewire\Livewire;

// use Relaticle\Flowforge\Livewire\KanbanBoard;

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
