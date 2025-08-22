<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Board\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Clean record management for Board (mirrors Filament's HasRecords).
 */
trait HasBoardRecords
{
    protected string $recordTitleAttribute = 'title';

    /**
     * Set the record title attribute.
     */
    public function recordTitleAttribute(string $attribute): static
    {
        $this->recordTitleAttribute = $attribute;

        return $this;
    }

    /**
     * Get the record title attribute.
     */
    public function getRecordTitleAttribute(): string
    {
        return $this->recordTitleAttribute;
    }

    /**
     * Get records for a specific column (simplified - direct query).
     */
    public function getBoardRecords(string $columnId): Collection
    {
        $query = $this->getQuery();

        if (! $query) {
            return collect();
        }

        $statusField = $this->getColumnIdentifierAttribute() ?? 'status';

        // Get pagination limit from Livewire if available
        $livewire = $this->getLivewire();
        $limit = 10; // default

        if (property_exists($livewire, 'columnCardLimits')) {
            $limit = $livewire->columnCardLimits[$columnId] ?? 10;
        }

        return (clone $query)
            ->where($statusField, $columnId)
            ->limit($limit)
            ->get();
    }

    /**
     * Get record count for a column (delegates to Livewire).
     */
    public function getBoardRecordCount(string $columnId): int
    {
        $livewire = $this->getLivewire();

        if (method_exists($livewire, 'getBoardColumnRecordCount')) {
            return $livewire->getBoardColumnRecordCount($columnId);
        }

        // Fallback: direct query
        $query = $this->getQuery();
        if ($query) {
            $statusField = $this->getColumnIdentifierAttribute() ?? 'status';

            return (clone $query)->where($statusField, $columnId)->count();
        }

        return 0;
    }

    /**
     * Get a single record by ID (delegates to Livewire).
     */
    public function getBoardRecord(int | string $recordId): ?Model
    {
        $livewire = $this->getLivewire();

        if (method_exists($livewire, 'getBoardRecord')) {
            return $livewire->getBoardRecord($recordId);
        }

        // Fallback: direct query
        $query = $this->getQuery();

        return $query ? (clone $query)->find($recordId) : null;
    }

    /**
     * Format a record for display with Infolist entries.
     */
    public function formatBoardRecord(Model $record): array
    {
        $formatted = [
            'id' => $record->getKey(),
            'title' => data_get($record, $this->getRecordTitleAttribute()),
            'column' => data_get($record, $this->getColumnIdentifierAttribute() ?? 'status'),
            'model' => $record,
        ];

        // Process card schema if available
        if (method_exists($this, 'getCardSchema')) {
            $schema = $this->getCardSchema();
            
            if ($schema !== null) {
                // The schema is already built and configured
                $schema->model($record);
                
                // Render the entire schema as HTML
                $formatted['schema_html'] = $schema->toHtml();
            }
        }

        return $formatted;
    }
}
