<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Contracts;

use Filament\Tables\Contracts\HasTable;
use Relaticle\Flowforge\Board;

interface HasBoard extends HasTable
{
    /**
     * Get the board configuration.
     */
    public function getBoard(): Board;

    /**
     * Configure the board.
     */
    public function board(Board $board): Board;
}
