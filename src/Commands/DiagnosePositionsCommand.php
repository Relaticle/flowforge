<?php

namespace Relaticle\Flowforge\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Relaticle\Flowforge\Services\DecimalPosition;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class DiagnosePositionsCommand extends Command
{
    protected $signature = 'flowforge:diagnose-positions
                            {--model= : Model class to diagnose (e.g., App\\Models\\Task)}
                            {--column= : Column identifier field}
                            {--position= : Position field name}';

    protected $description = 'Diagnose position column issues including gaps, inversions, and duplicates';

    public function handle(): int
    {
        $this->displayHeader();

        // Get parameters (interactive or from options)
        $model = $this->option('model') ?? text(
            label: 'Model class (e.g., App\\Models\\Task)',
            required: true,
            validate: fn (string $value) => $this->validateModelClass($value)
        );

        $columnField = $this->option('column') ?? text(
            label: 'Column identifier field (for grouping)',
            placeholder: 'status',
            required: true
        );

        $positionField = $this->option('position') ?? text(
            label: 'Position field',
            default: 'position',
            required: true
        );

        // Validate model
        if (! class_exists($model)) {
            error("Model class '{$model}' does not exist");

            return self::FAILURE;
        }

        $modelInstance = new $model;
        if (! $modelInstance instanceof Model) {
            error("Class '{$model}' is not an Eloquent model");

            return self::FAILURE;
        }

        // Display configuration
        info("Model: {$model}");
        info("Column Identifier: {$columnField}");
        info("Position Identifier: {$positionField}");
        $this->newLine();

        // Run diagnostics
        $issues = [];

        // 1. Check for small gaps (needs rebalancing)
        $this->line('Checking position gaps...');
        $gapIssues = $this->checkGaps($modelInstance, $columnField, $positionField);
        if (count($gapIssues) > 0) {
            $issues = array_merge($issues, $gapIssues);
        }

        // 2. Check for position inversions
        $this->line('Scanning for position inversions...');
        $inversionIssues = $this->checkInversions($modelInstance, $columnField, $positionField);
        if (count($inversionIssues) > 0) {
            $issues = array_merge($issues, $inversionIssues);
        }

        // 3. Check for duplicates
        $this->line('Checking for duplicate positions...');
        $duplicateIssues = $this->checkDuplicates($modelInstance, $columnField, $positionField);
        if (count($duplicateIssues) > 0) {
            $issues = array_merge($issues, $duplicateIssues);
        }

        // 4. Check for null positions
        $this->line('Checking for null positions...');
        $nullIssue = $this->checkNullPositions($modelInstance, $positionField);
        if ($nullIssue) {
            $issues[] = $nullIssue;
        }

        $this->newLine();

        // Display results
        if (empty($issues)) {
            info('All checks passed! No issues detected.');

            return self::SUCCESS;
        }

        warning(sprintf('Found %d issue(s):', count($issues)));
        $this->newLine();

        foreach ($issues as $index => $issue) {
            $this->displayIssue($index + 1, $issue);
        }

        return self::FAILURE;
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->line('Flowforge Position Diagnostics');
        $this->line('===============================');
        $this->newLine();
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

    private function checkGaps(Model $model, string $columnField, string $positionField): array
    {
        $issues = [];
        $columns = $model->query()->distinct()->pluck($columnField)->map(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value);

        foreach ($columns as $column) {
            $positions = $model->query()
                ->where($columnField, $column)
                ->whereNotNull($positionField)
                ->orderBy($positionField)
                ->orderBy('id')
                ->pluck($positionField);

            if ($positions->count() < 2) {
                continue;
            }

            $smallGaps = 0;
            $minGap = null;

            for ($i = 0; $i < $positions->count() - 1; $i++) {
                $current = DecimalPosition::normalize($positions[$i]);
                $next = DecimalPosition::normalize($positions[$i + 1]);
                $gap = DecimalPosition::gap($current, $next);

                if ($minGap === null || DecimalPosition::lessThan($gap, $minGap)) {
                    $minGap = $gap;
                }

                if (DecimalPosition::needsRebalancing($current, $next)) {
                    $smallGaps++;
                }
            }

            if ($smallGaps > 0) {
                $issues[] = [
                    'type' => 'small_gaps',
                    'severity' => 'medium',
                    'column' => $column,
                    'count' => $smallGaps,
                    'min_gap' => $minGap,
                ];
            }
        }

        if (empty($issues)) {
            info('  No small gaps detected (no rebalancing needed)');
        }

        return $issues;
    }

    private function checkInversions(Model $model, string $columnField, string $positionField): array
    {
        $issues = [];
        $columns = $model->query()->distinct()->pluck($columnField)->map(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value);

        foreach ($columns as $column) {
            $records = $model->query()
                ->where($columnField, $column)
                ->whereNotNull($positionField)
                ->orderBy('id')
                ->get();

            if ($records->count() < 2) {
                continue;
            }

            $inversions = [];
            for ($i = 0; $i < $records->count() - 1; $i++) {
                $current = $records[$i];
                $next = $records[$i + 1];

                $currentPos = DecimalPosition::normalize($current->getAttribute($positionField));
                $nextPos = DecimalPosition::normalize($next->getAttribute($positionField));

                // Check if positions are inverted (current >= next when they should be current < next)
                if (DecimalPosition::compare($currentPos, $nextPos) >= 0) {
                    $inversions[] = [
                        'current_id' => $current->getKey(),
                        'current_pos' => $currentPos,
                        'next_id' => $next->getKey(),
                        'next_pos' => $nextPos,
                    ];
                }
            }

            if (count($inversions) > 0) {
                $issues[] = [
                    'type' => 'inversion',
                    'severity' => 'high',
                    'column' => $column,
                    'count' => count($inversions),
                    'examples' => array_slice($inversions, 0, 3), // Show first 3 examples
                ];
            }
        }

        if (empty($issues)) {
            info('  No position inversions detected');
        }

        return $issues;
    }

    private function checkDuplicates(Model $model, string $columnField, string $positionField): array
    {
        $issues = [];
        $columns = $model->query()->distinct()->pluck($columnField)->map(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value);

        foreach ($columns as $column) {
            $duplicates = DB::table($model->getTable())
                ->select($positionField, DB::raw('COUNT(*) as duplicate_count'))
                ->where($columnField, $column)
                ->whereNotNull($positionField)
                ->groupBy($positionField)
                ->havingRaw('COUNT(*) > 1')
                ->get();

            if ($duplicates->count() > 0) {
                $issues[] = [
                    'type' => 'duplicate',
                    'severity' => 'medium',
                    'column' => $column,
                    'count' => $duplicates->sum('duplicate_count'),
                    'unique_positions' => $duplicates->count(),
                ];
            }
        }

        if (empty($issues)) {
            info('  No duplicate positions detected');
        }

        return $issues;
    }

    private function checkNullPositions(Model $model, string $positionField): ?array
    {
        $nullCount = $model->query()->whereNull($positionField)->count();

        if ($nullCount === 0) {
            info('  No null positions detected');

            return null;
        }

        return [
            'type' => 'null',
            'severity' => 'low',
            'count' => $nullCount,
        ];
    }

    private function displayIssue(int $number, array $issue): void
    {
        $this->line("Issue #{$number}: " . strtoupper($issue['type']));

        if ($issue['type'] === 'small_gaps') {
            warning("  Found {$issue['count']} position pair(s) with gap below " . DecimalPosition::MIN_GAP . " in column '{$issue['column']}'");
            $this->line("     Minimum gap found: {$issue['min_gap']}");
            $this->line('     This may cause precision issues. Consider running: php artisan flowforge:rebalance-positions');
            $this->newLine();
        }

        if ($issue['type'] === 'inversion') {
            error("  Found {$issue['count']} inverted position pair(s) in column '{$issue['column']}':");
            foreach ($issue['examples'] as $example) {
                $this->line("     - Record #{$example['current_id']} (pos: {$example['current_pos']}) >= Record #{$example['next_id']} (pos: {$example['next_pos']})");
            }
            $this->newLine();
        }

        if ($issue['type'] === 'duplicate') {
            warning("  Found {$issue['count']} duplicate positions in column '{$issue['column']}'");
            $this->line("     ({$issue['unique_positions']} unique position values with duplicates)");
            $this->newLine();
        }

        if ($issue['type'] === 'null') {
            info("  Found {$issue['count']} records with null positions");
            $this->newLine();
        }

        info('  After fixing issues, run: php artisan flowforge:repair-positions');
        $this->newLine();
    }
}
