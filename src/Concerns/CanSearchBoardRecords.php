<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;

/**
 * Search functionality for Board (mirrors Filament's CanSearchRecords).
 */
trait CanSearchBoardRecords
{
    protected array $searchableFields = [];

    protected bool $isSearchable = false;

    /**
     * Make the board searchable.
     */
    public function searchable(array | Closure $fields = []): static
    {
        $this->searchableFields = $this->evaluate($fields);
        $this->isSearchable = true;

        return $this;
    }

    /**
     * Check if the board is searchable.
     */
    public function isSearchable(): bool
    {
        return $this->isSearchable && ! empty($this->searchableFields);
    }

    /**
     * Get searchable fields.
     */
    public function getSearchableFields(): array
    {
        return $this->searchableFields;
    }
}
