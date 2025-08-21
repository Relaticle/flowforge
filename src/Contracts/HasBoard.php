<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;

interface HasBoard
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
