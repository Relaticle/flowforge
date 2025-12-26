<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles rebalancing of positions within a column when gaps become too small.
 *
 * This service redistributes positions evenly using the default gap, ensuring
 * consistent spacing and preventing precision exhaustion after many insertions.
 */
final readonly class PositionRebalancer
{
    /**
     * Rebalance all positions in a column.
     *
     * @param  Builder  $query  The base query for the model
     * @param  string  $columnField  The field identifying the column (e.g., 'status')
     * @param  string  $columnId  The column value to rebalance
     * @param  string  $positionField  The field storing positions (e.g., 'position')
     * @return int Number of records rebalanced
     */
    public function rebalanceColumn(
        Builder $query,
        string $columnField,
        string $columnId,
        string $positionField
    ): int {
        $keyName = $query->getModel()->getKeyName();
        $records = (clone $query)
            ->where($columnField, $columnId)
            ->whereNotNull($positionField)
            ->orderBy($positionField)
            ->orderBy($keyName) // Tie-breaker for deterministic order
            ->get();

        if ($records->isEmpty()) {
            return 0;
        }

        $positions = DecimalPosition::generateSequence($records->count());

        DB::transaction(function () use ($records, $positions, $positionField) {
            foreach ($records as $index => $record) {
                /** @var Model $record */
                $record->update([$positionField => $positions[$index]]);
            }
        });

        Log::info('Flowforge: Rebalanced column positions', [
            'column' => $columnId,
            'count' => $records->count(),
        ]);

        return $records->count();
    }

    /**
     * Check if a column needs rebalancing by scanning for small gaps.
     *
     * @param  Builder  $query  The base query for the model
     * @param  string  $columnField  The field identifying the column
     * @param  string  $columnId  The column value to check
     * @param  string  $positionField  The field storing positions
     */
    public function needsRebalancing(
        Builder $query,
        string $columnField,
        string $columnId,
        string $positionField
    ): bool {
        $positions = (clone $query)
            ->where($columnField, $columnId)
            ->whereNotNull($positionField)
            ->orderBy($positionField)
            ->pluck($positionField);

        return $this->hasSmallGaps($positions);
    }

    /**
     * Find columns that need rebalancing.
     *
     * @param  Builder  $query  The base query for the model
     * @param  string  $columnField  The field identifying the column
     * @param  string  $positionField  The field storing positions
     * @return Collection<int, string> Column IDs that need rebalancing
     */
    public function findColumnsNeedingRebalancing(
        Builder $query,
        string $columnField,
        string $positionField
    ): Collection {
        $columns = (clone $query)
            ->select($columnField)
            ->distinct()
            ->pluck($columnField);

        return $columns->filter(function ($columnId) use ($query, $columnField, $positionField) {
            return $this->needsRebalancing($query, $columnField, (string) $columnId, $positionField);
        })->values();
    }

    /**
     * Rebalance all columns that need it.
     *
     * @param  Builder  $query  The base query for the model
     * @param  string  $columnField  The field identifying the column
     * @param  string  $positionField  The field storing positions
     * @return array<string, int> Map of column ID to records rebalanced
     */
    public function rebalanceAll(
        Builder $query,
        string $columnField,
        string $positionField
    ): array {
        $results = [];

        $columnsNeedingRebalancing = $this->findColumnsNeedingRebalancing(
            $query,
            $columnField,
            $positionField
        );

        foreach ($columnsNeedingRebalancing as $columnId) {
            $results[(string) $columnId] = $this->rebalanceColumn(
                $query,
                $columnField,
                (string) $columnId,
                $positionField
            );
        }

        return $results;
    }

    /**
     * Get gap statistics for a column.
     *
     * @param  Builder  $query  The base query for the model
     * @param  string  $columnField  The field identifying the column
     * @param  string  $columnId  The column value to analyze
     * @param  string  $positionField  The field storing positions
     * @return array{count: int, min_gap: string|null, max_gap: string|null, avg_gap: string|null, small_gaps: int}
     */
    public function getGapStatistics(
        Builder $query,
        string $columnField,
        string $columnId,
        string $positionField
    ): array {
        $positions = (clone $query)
            ->where($columnField, $columnId)
            ->whereNotNull($positionField)
            ->orderBy($positionField)
            ->pluck($positionField)
            ->map(fn ($p) => DecimalPosition::normalize($p))
            ->values();

        if ($positions->count() < 2) {
            return [
                'count' => $positions->count(),
                'min_gap' => null,
                'max_gap' => null,
                'avg_gap' => null,
                'small_gaps' => 0,
            ];
        }

        $gaps = [];
        $smallGapCount = 0;

        for ($i = 1; $i < $positions->count(); $i++) {
            $gap = DecimalPosition::gap($positions[$i - 1], $positions[$i]);
            $gaps[] = $gap;

            if (bccomp($gap, DecimalPosition::MIN_GAP, DecimalPosition::SCALE) < 0) {
                $smallGapCount++;
            }
        }

        // Calculate min/max/avg using bcmath
        $minGap = $gaps[0];
        $maxGap = $gaps[0];
        $totalGap = '0';

        foreach ($gaps as $gap) {
            if (bccomp($gap, $minGap, DecimalPosition::SCALE) < 0) {
                $minGap = $gap;
            }
            if (bccomp($gap, $maxGap, DecimalPosition::SCALE) > 0) {
                $maxGap = $gap;
            }
            $totalGap = bcadd($totalGap, $gap, DecimalPosition::SCALE);
        }

        $avgGap = bcdiv($totalGap, (string) count($gaps), DecimalPosition::SCALE);

        return [
            'count' => $positions->count(),
            'min_gap' => $minGap,
            'max_gap' => $maxGap,
            'avg_gap' => $avgGap,
            'small_gaps' => $smallGapCount,
        ];
    }

    /**
     * Check if a collection of positions has any gaps below MIN_GAP.
     *
     * @param  Collection<int, mixed>  $positions
     */
    private function hasSmallGaps(Collection $positions): bool
    {
        if ($positions->count() < 2) {
            return false;
        }

        $normalized = $positions
            ->map(fn ($p) => DecimalPosition::normalize($p))
            ->values();

        for ($i = 1; $i < $normalized->count(); $i++) {
            if (DecimalPosition::needsRebalancing($normalized[$i - 1], $normalized[$i])) {
                return true;
            }
        }

        return false;
    }
}
