<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Relaticle\Flowforge\Board;

trait InteractsWithBoard
{
    protected Board $board;

    /**
     * Get the board configuration.
     */
    public function getBoard(): Board
    {
        return $this->board;
    }

    /**
     * Boot the InteractsWithBoard trait.
     */
    public function bootedInteractsWithBoard(): void
    {
        $this->board = $this->board($this->makeBoard());
    }

    protected function makeBoard(): Board
    {
        return Board::make($this)
            ->query(fn (): Builder | Relation | null => $this->getBoardQuery());
    }
}
