<?php

namespace Relaticle\Flowforge\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Relaticle\Flowforge\Services\DecimalPosition;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class RepairPositionsCommand extends Command
{
    protected $signature = 'flowforge:repair-positions 
                            {--dry-run : Show what would be changed without applying}
                            {--ids= : Comma-separated list of IDs to repair}
                            {--where= : Custom WHERE clause (e.g., "team_id=5")}';

    protected $description = 'Interactive command to repair and regenerate position fields for drag-and-drop ordering';

    public function handle(): int
    {
        info('🔧 Flowforge Position Repair Tool');

        // Get model
        $model = text(
            label: 'Model class (e.g., App\\Models\\Task)',
            required: true,
            validate: fn (string $value) => $this->validateModelClass($value)
        );

        // Get column identifier field
        $columnField = text(
            label: 'Column identifier field (for grouping)',
            placeholder: 'status',
            required: true
        );

        // Get position field
        $positionField = text(
            label: 'Position field',
            default: 'position',
            required: true
        );

        // Validate model has the fields
        $modelInstance = new $model;
        if (! $this->validateFields($modelInstance, $columnField, $positionField)) {
            return self::FAILURE;
        }

        // Apply filtering options
        $baseQuery = $model::query();
        $baseQuery = $this->applyFilters($baseQuery);

        // Analyze current state
        $analysis = $this->analyzePositions($model, $columnField, $positionField, $baseQuery);

        if ($analysis['total'] === 0) {
            info('✅ No records found to process.');

            return self::SUCCESS;
        }

        $this->displayAnalysis($analysis);

        // Select repair strategy
        $strategy = select(
            label: 'Repair strategy',
            options: [
                'regenerate' => 'Regenerate all positions (fresh start)',
                'fix_missing' => 'Fix missing positions only',
                'fix_duplicates' => 'Fix duplicate positions only',
                'fix_all' => 'Fix missing + duplicates (recommended)',
            ],
            default: 'fix_all'
        );

        // Show preview
        $changes = $this->calculateChanges($model, $columnField, $positionField, $strategy, $baseQuery);
        $this->displayPreview($changes);

        // Confirm execution
        if (! $this->option('dry-run')) {
            if (! confirm('Apply these changes?', true)) {
                info('Operation cancelled.');

                return self::SUCCESS;
            }
        } else {
            info('🔍 Dry run complete - no changes applied.');

            return self::SUCCESS;
        }

        // Apply changes
        $this->applyChanges($model, $positionField, $changes);

        info('✅ Position repair completed successfully!');

        return self::SUCCESS;
    }

    private function validateModelClass(string $value): ?string
    {
        if (! class_exists($value)) {
            return "Model class '{$value}' does not exist";
        }

        if (! is_subclass_of($value, Model::class)) {
            return "Class '{$value}' is not an Eloquent model";
        }

        return null;
    }

    private function validateFields(Model $model, string $columnField, string $positionField): bool
    {
        $fillable = $model->getFillable();
        $table = $model->getTable();

        if (! in_array($columnField, $fillable) || ! in_array($positionField, $fillable)) {
            warning("Fields {$columnField} and {$positionField} should be fillable on {$table} table");
        }

        return true;
    }

    private function applyFilters($query)
    {
        // Apply ID filtering
        if ($ids = $this->option('ids')) {
            $idArray = explode(',', $ids);
            $query->whereIn('id', array_map('trim', $idArray));
        }

        // Apply custom WHERE clause
        if ($where = $this->option('where')) {
            // Simple parsing for basic conditions like "column=value" or "column>value"
            if (preg_match('/^(\w+)\s*([=<>!]+)\s*(.+)$/', $where, $matches)) {
                [$_, $column, $operator, $value] = $matches;
                $query->where($column, $operator, $value);
            } else {
                warning("Invalid WHERE clause format: {$where}. Use format like 'column=value'");
            }
        }

        return $query;
    }

    private function convertEnumToString($value): string
    {
        if (is_object($value)) {
            // Handle Laravel Enums (implements UnitEnum)
            if ($value instanceof \UnitEnum) {
                return $value->value ?? $value->name;
            }
            // Handle objects with value property
            if (property_exists($value, 'value')) {
                return (string) $value->value;
            }
            // Handle objects with __toString method
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            // Fallback: try to get class name or serialize
            return class_basename($value);
        }

        return (string) $value;
    }

    /**
     * @param  class-string<Model>  $model
     * @return array{total: int, null_positions: int, duplicates: int, groups: array<string, int>}
     */
    private function analyzePositions(string $model, string $columnField, string $positionField, $baseQuery = null): array
    {
        $query = $baseQuery ?: $model::query();
        $modelInstance = new $model;
        $table = $modelInstance->getTable();

        // Get basic counts using filtered queries
        $total = (clone $query)->count();
        $nullPositions = (clone $query)->whereNull($positionField)->count();

        // Find duplicates using the filtered query
        $filteredIds = (clone $query)->pluck('id')->toArray();
        if (empty($filteredIds)) {
            $duplicates = 0;
        } else {
            $duplicates = DB::table($table)
                ->select($positionField)
                ->whereIn('id', $filteredIds)
                ->whereNotNull($positionField)
                ->groupBy($positionField)
                ->havingRaw('COUNT(*) > 1')
                ->count();
        }

        // Group analysis with filtering
        $groups = (clone $query)
            ->select($columnField, DB::raw('COUNT(*) as record_count'))
            ->groupBy($columnField)
            ->pluck('record_count', $columnField)
            ->mapWithKeys(function ($count, $key) {
                // Convert enum to string value if needed
                $stringKey = $this->convertEnumToString($key);

                return [$stringKey => $count];
            })
            ->toArray();

        return [
            'total' => $total,
            'null_positions' => $nullPositions,
            'duplicates' => $duplicates,
            'groups' => $groups,
        ];
    }

    /**
     * @param  array{total: int, null_positions: int, duplicates: int, groups: array<string, int>}  $analysis
     */
    private function displayAnalysis(array $analysis): void
    {
        info('📊 Analysis Results:');
        $this->line("   Total records: {$analysis['total']}");
        $this->line("   Missing positions: {$analysis['null_positions']}");
        $this->line("   Duplicate positions: {$analysis['duplicates']}");

        $this->line("\n📋 Groups:");
        foreach ($analysis['groups'] as $group => $count) {
            $this->line("   {$group}: {$count} records");
        }
        $this->newLine();
    }

    /**
     * @param  class-string<Model>  $model
     * @return array<string, array<int, string>>
     */
    private function calculateChanges(string $model, string $columnField, string $positionField, string $strategy, $baseQuery = null): array
    {
        $changes = [];
        $query = $baseQuery ?: $model::query();

        // Get all groups from filtered query
        $groups = (clone $query)
            ->select($columnField)
            ->distinct()
            ->pluck($columnField);

        foreach ($groups as $group) {
            // Convert enum to string for array key
            $groupKey = $this->convertEnumToString($group);

            $records = $this->getRecordsForStrategy($model, $columnField, $positionField, $group, $strategy, $query);

            if ($records->isNotEmpty()) {
                $changes[$groupKey] = $this->generatePositions($records, $strategy);
            }
        }

        return $changes;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function getRecordsForStrategy(string $model, string $columnField, string $positionField, mixed $group, string $strategy, $baseQuery = null): Collection
    {
        $query = $baseQuery ? (clone $baseQuery)->where($columnField, $group) : $model::where($columnField, $group);
        $keyName = $query->getModel()->getKeyName();

        return match ($strategy) {
            'regenerate' => $query->orderBy($keyName)->get(),
            'fix_missing' => $query->whereNull($positionField)->orderBy($keyName)->get(),
            'fix_duplicates' => $this->getDuplicateRecords($query, $positionField),
            'fix_all' => $query->where(function ($q) use ($positionField) {
                $q->whereNull($positionField)
                    ->orWhereIn($positionField, $this->getDuplicatePositions($q, $positionField));
            })->orderBy($keyName)->get(),
            default => new Collection,
        };
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function getDuplicateRecords(Builder $query, string $positionField): Collection
    {
        $duplicatePositions = $this->getDuplicatePositions($query, $positionField);

        if (empty($duplicatePositions)) {
            return new Collection;
        }

        $keyName = $query->getModel()->getKeyName();

        return $query->whereIn($positionField, $duplicatePositions)->orderBy($keyName)->get();
    }

    /**
     * @param  Builder<Model>  $query
     * @return string[]
     */
    private function getDuplicatePositions(Builder $query, string $positionField): array
    {
        return $query->select($positionField)
            ->whereNotNull($positionField)
            ->groupBy($positionField)
            ->havingRaw('COUNT(*) > 1')
            ->pluck($positionField)
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    private function generatePositions(iterable $records, string $strategy): array
    {
        $positions = [];
        $lastPosition = null;

        foreach ($records as $record) {
            $positionValue = $record instanceof Model ? $record->getAttribute('position') : $record->position ?? null;
            if ($strategy === 'regenerate' || is_null($positionValue)) {
                $newPosition = $lastPosition !== null
                    ? DecimalPosition::after($lastPosition)
                    : DecimalPosition::forEmptyColumn();
                $recordId = $record instanceof Model ? $record->getKey() : $record->id;
                $positions[$recordId] = $newPosition;
                $lastPosition = $newPosition;
            }
        }

        return $positions;
    }

    /**
     * @param  array<string, array<int, string>>  $changes
     */
    private function displayPreview(array $changes): void
    {
        if (empty($changes)) {
            info('✅ No changes needed.');

            return;
        }

        info('📋 Preview of changes:');
        foreach ($changes as $group => $positions) {
            $count = count($positions);
            $this->line("   {$group}: {$count} records to update");
        }
        $this->newLine();
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<string, array<int, string>>  $changes
     *
     * @throws \Throwable
     */
    private function applyChanges(string $model, string $positionField, array $changes): void
    {
        DB::transaction(function () use ($model, $positionField, $changes) {
            foreach ($changes as $positions) {
                foreach ($positions as $id => $position) {
                    $model::where('id', $id)->update([$positionField => $position]);
                }
            }
        });
    }
}
