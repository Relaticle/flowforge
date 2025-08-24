<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Relaticle\Flowforge\Concerns\BaseBoard;
use Relaticle\Flowforge\Contracts\HasBoard;

/**
 * Board page for standard Filament pages.
 * Extends Filament's base Page class with kanban board functionality.
 */
abstract class BoardPage extends Page implements HasActions, HasBoard, HasForms
{
    use BaseBoard;

    protected string $view = 'flowforge::filament.pages.board-page';
}
