<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Relaticle\Flowforge\Concerns\BaseBoard;
use Relaticle\Flowforge\Contracts\HasBoard;

/**
 * Board page for Filament resource pages.
 * Extends Filament's resource Page class with kanban board functionality.
 */
abstract class BoardResourcePage extends Page implements HasActions, HasBoard, HasForms
{
    use BaseBoard;

    protected string $view = 'flowforge::filament.pages.board-page';
}
