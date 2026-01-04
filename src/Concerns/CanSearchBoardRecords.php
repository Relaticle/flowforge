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
    public function searchable(string | array | Closure $fields = []): static
    {
        // Ensure fields is an array
        $searchableFields = is_string($fields) ? [$fields] : $fields;
        
        $this->searchableFields = $this->evaluate($searchableFields);
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
