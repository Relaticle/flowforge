<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Concerns\InteractsWithBoard;
use Relaticle\Flowforge\Contracts\HasBoard;

/**
 * Simplified BoardPage - just like Filament's pages.
 * No adapters, no complex caching - just clean delegation.
 */
abstract class BoardPage extends Page implements HasActions, HasBoard, HasForms
{
    use InteractsWithActions;
    use InteractsWithBoard {
        InteractsWithBoard::getDefaultActionRecord insteadof InteractsWithActions;
    }
    use InteractsWithForms;

    protected string $view = 'flowforge::filament.pages.board-page';

    /**
     * Configure the board - implement in subclasses.
     */
    abstract public function board(Board $board): Board;
}
