<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Relaticle\Flowforge\Board;

/**
 * Base functionality for all board pages.
 * Provides common setup for both regular pages and resource pages.
 */
trait BaseBoard
{
    use InteractsWithActions;
    use InteractsWithBoard {
        InteractsWithBoard::getDefaultActionRecord insteadof InteractsWithActions;
    }
    use InteractsWithForms;

    /**
     * Configure the board - implement in subclasses.
     */
    abstract public function board(Board $board): Board;

    /**
     * Get the board view for rendering.
     */
    protected function getBoardView(): string
    {
        return 'flowforge::filament.pages.board-page';
    }
}
