<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Relaticle\Flowforge\Board;

trait BelongsToBoard
{
    protected ?Board $board = null;

    public function board(?Board $board): static
    {
        $this->board = $board;

        return $this;
    }

    public function getBoard(): ?Board
    {
        return $this->board;
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'board' => [$this->getBoard()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
