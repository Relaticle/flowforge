<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Enterprise-grade record management for Board with cursor-based pagination.
 * Implements position-based ordering for optimal performance at scale.
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
     * Get records for a specific column with cursor-based pagination.
     * Uses position field for optimal performance with large datasets.
     */
    public function getBoardRecords(string $columnId): Collection
    {
        $query = $this->getQuery();

        if (! $query) {
            return new Collection;
        }

        $statusField = $this->getColumnIdentifierAttribute() ?? 'status';
        $livewire = $this->getLivewire();

        $limit = property_exists($livewire, 'columnCardLimits')
            ? ($livewire->columnCardLimits[$columnId] ?? 20)
            : 20;

        $queryClone = (clone $query)->where($statusField, $columnId);

        // Apply table filters using Filament's native system
        if (method_exists($livewire, 'getTable') && $livewire->getTable()->isFilterable()) {
            $baseQuery = $livewire->getFilteredTableQuery();
            if ($baseQuery) {
                $queryClone = (clone $baseQuery)->where($statusField, $columnId);
            }
        }

        $positionField = $this->getBoardPositionField();

        if ($this->modelHasColumn($queryClone->getModel(), $positionField)) {
            $queryClone->orderBy($positionField, 'asc');
        }

        return $queryClone->limit($limit)->get();
    }

    /**
     * Get record count for a column (direct query with filters).
     */
    public function getBoardRecordCount(string $columnId): int
    {
        $query = $this->getQuery();

        if (! $query) {
            return 0;
        }

        $statusField = $this->getColumnIdentifierAttribute() ?? 'status';
        $queryClone = (clone $query)->where($statusField, $columnId);

        // Apply table filters using Filament's native system
        $livewire = $this->getLivewire();
        if (method_exists($livewire, 'getTable') && $livewire->getTable()->isFilterable()) {
            // Use Filament's native filtered query
            $baseQuery = $livewire->getFilteredTableQuery();
            if ($baseQuery) {
                $queryClone = (clone $baseQuery)->where($statusField, $columnId);
            }
        }

        return $queryClone->count();
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
            'position' => data_get($record, $this->getBoardPositionField()),
            'model' => $record,
        ];

        // Process card schema if available
        if (method_exists($this, 'getCardSchema')) {
            $schema = $this->getCardSchema();

            if ($schema !== null) {
                // The schema is already built and configured
                $schema->model($record);

                // Store the schema object with record context for proper Livewire rendering
                $formatted['schema'] = $schema;
            }
        }

        return $formatted;
    }

    /**
     * Get the position field name for ordering.
     */
    public function getPositionField(): string
    {
        return 'position';
    }

    /**
     * Check if model has the specified column.
     */
    protected function modelHasColumn($model, string $columnName): bool
    {
        try {
            $table = $model->getTable();
            $schema = $model->getConnection()->getSchemaBuilder();

            return $schema->hasColumn($table, $columnName);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get records before a specific position (for inserting cards).
     */
    public function getRecordsBeforePosition(string $columnId, string $position, int $limit = 5): Collection
    {
        $query = $this->getQuery();

        if (! $query) {
            return new Collection;
        }

        $statusField = $this->getColumnIdentifierAttribute() ?? 'status';
        $positionField = $this->getBoardPositionField();

        return (clone $query)
            ->where($statusField, $columnId)
            ->where($positionField, '<', $position)
            ->orderBy($positionField, 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get records after a specific position (for inserting cards).
     */
    public function getRecordsAfterPosition(string $columnId, string $position, int $limit = 5): Collection
    {
        $query = $this->getQuery();

        if (! $query) {
            return new Collection;
        }

        $statusField = $this->getColumnIdentifierAttribute() ?? 'status';
        $positionField = $this->getBoardPositionField();

        return (clone $query)
            ->where($statusField, $columnId)
            ->where($positionField, '>', $position)
            ->orderBy($positionField, 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the last position in a column.
     */
    public function getLastPositionInColumn(string $columnId): ?string
    {
        $query = $this->getQuery();

        if (! $query) {
            return null;
        }

        $statusField = $this->getColumnIdentifierAttribute() ?? 'status';
        $positionField = $this->getBoardPositionField();

        $record = (clone $query)
            ->where($statusField, $columnId)
            ->orderBy($positionField, 'desc')
            ->first();

        return $record?->getAttribute($positionField);
    }
}
