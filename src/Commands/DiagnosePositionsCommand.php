<?php

namespace Relaticle\Flowforge\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class DiagnosePositionsCommand extends Command
{
    protected $signature = 'flowforge:diagnose-positions
                            {--model= : Model class to diagnose (e.g., App\\Models\\Task)}
                            {--column= : Column identifier field}
                            {--position= : Position field name}
                            {--fix : Automatically apply collation fixes}';

    protected $description = 'Diagnose position column collation and ordering issues';

    private array $expectedCollations = [
        'mysql' => 'utf8mb4_bin',
        'pgsql' => 'C',
        'sqlsrv' => 'Latin1_General_BIN2',
        'sqlite' => null, // SQLite doesn't need collation
    ];

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
        info("✓ Model: {$model}");
        info("✓ Column Identifier: {$columnField}");
        info("✓ Position Identifier: {$positionField}");
        $this->newLine();

        // Run diagnostics
        $issues = [];

        // 1. Check collation
        $this->line('🔍 Checking database collation...');
        $collationIssue = $this->checkCollation($modelInstance, $positionField);
        if ($collationIssue) {
            $issues[] = $collationIssue;
        }

        // 2. Check for position inversions
        $this->line('🔍 Scanning for position inversions...');
        $inversionIssues = $this->checkInversions($modelInstance, $columnField, $positionField);
        if (count($inversionIssues) > 0) {
            $issues = array_merge($issues, $inversionIssues);
        }

        // 3. Check for duplicates
        $this->line('🔍 Checking for duplicate positions...');
        $duplicateIssues = $this->checkDuplicates($modelInstance, $columnField, $positionField);
        if (count($duplicateIssues) > 0) {
            $issues = array_merge($issues, $duplicateIssues);
        }

        // 4. Check for null positions
        $this->line('🔍 Checking for null positions...');
        $nullIssue = $this->checkNullPositions($modelInstance, $positionField);
        if ($nullIssue) {
            $issues[] = $nullIssue;
        }

        $this->newLine();

        // Display results
        if (empty($issues)) {
            info('✅ All checks passed! No issues detected.');

            return self::SUCCESS;
        }

        warning(sprintf('⚠️  Found %d issue(s):', count($issues)));
        $this->newLine();

        foreach ($issues as $index => $issue) {
            $this->displayIssue($index + 1, $issue);
        }

        // Offer fixes if --fix option is provided
        if ($this->option('fix') && isset($collationIssue)) {
            $this->applyCollationFix($modelInstance, $positionField);
        }

        return self::FAILURE;
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════════╗');
        $this->line('║              Flowforge Position Diagnostics                   ║');
        $this->line('╚══════════════════════════════════════════════════════════════╝');
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

    private function checkCollation(Model $model, string $positionField): ?array
    {
        $connection = $model->getConnection();
        $driver = $connection->getDriverName();
        $table = $model->getTable();

        // Skip for SQLite (no collation needed)
        if ($driver === 'sqlite') {
            info('  ✓ SQLite database - no collation check needed');

            return null;
        }

        $expectedCollation = $this->expectedCollations[$driver] ?? null;

        if (! $expectedCollation) {
            warning("  ⚠️  Unknown database driver: {$driver}");

            return null;
        }

        // Get actual collation
        $actualCollation = $this->getColumnCollation($connection, $table, $positionField);

        if ($actualCollation === $expectedCollation) {
            info("  ✓ Collation correct: {$actualCollation}");

            return null;
        }

        return [
            'type' => 'collation',
            'severity' => 'critical',
            'table' => $table,
            'column' => $positionField,
            'expected' => $expectedCollation,
            'actual' => $actualCollation ?? 'unknown',
            'driver' => $driver,
        ];
    }

    private function getColumnCollation($connection, string $table, string $column): ?string
    {
        $driver = $connection->getDriverName();

        try {
            if ($driver === 'mysql') {
                $result = DB::select("SHOW FULL COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);

                return $result[0]->Collation ?? null;
            }

            if ($driver === 'pgsql') {
                $result = DB::select('
                    SELECT collation_name
                    FROM information_schema.columns
                    WHERE table_name = ? AND column_name = ?
                ', [$table, $column]);

                return $result[0]->collation_name ?? null;
            }

            if ($driver === 'sqlsrv') {
                $result = DB::select('
                    SELECT COLLATION_NAME
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = ? AND COLUMN_NAME = ?
                ', [$table, $column]);

                return $result[0]->COLLATION_NAME ?? null;
            }
        } catch (\Exception $e) {
            warning("  Could not determine collation: {$e->getMessage()}");
        }

        return null;
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

                $currentPos = $current->getAttribute($positionField);
                $nextPos = $next->getAttribute($positionField);

                // Check if positions are inverted (current >= next when they should be current < next)
                if (strcmp($currentPos, $nextPos) >= 0) {
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
            info('  ✓ No position inversions detected');
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
            info('  ✓ No duplicate positions detected');
        }

        return $issues;
    }

    private function checkNullPositions(Model $model, string $positionField): ?array
    {
        $nullCount = $model->query()->whereNull($positionField)->count();

        if ($nullCount === 0) {
            info('  ✓ No null positions detected');

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
        $severityColors = [
            'critical' => 'error',
            'high' => 'error',
            'medium' => 'warning',
            'low' => 'info',
        ];

        $color = $severityColors[$issue['severity']] ?? 'info';

        $this->line("Issue #{$number}: " . strtoupper($issue['type']));

        if ($issue['type'] === 'collation') {
            error('  ❌ COLLATION MISMATCH');
            $this->line("     Expected: {$issue['expected']} (binary comparison)");
            $this->line("     Found: {$issue['actual']} (case-insensitive comparison)");
            $this->newLine();
            $this->line('     This causes incorrect position ordering!');
            $this->newLine();
            warning('  🔧 Fix: Run this migration to correct collation:');
            $this->newLine();

            if ($issue['driver'] === 'mysql') {
                $this->line("     ALTER TABLE {$issue['table']} MODIFY {$issue['column']} VARCHAR(255)");
                $this->line('     CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;');
            } elseif ($issue['driver'] === 'pgsql') {
                $this->line("     ALTER TABLE {$issue['table']} ALTER COLUMN {$issue['column']}");
                $this->line('     TYPE VARCHAR(255) COLLATE "C";');
            }
            $this->newLine();
        }

        if ($issue['type'] === 'inversion') {
            error("  ❌ Found {$issue['count']} inverted position pair(s) in column '{$issue['column']}':");
            foreach ($issue['examples'] as $example) {
                $this->line("     - Card #{$example['current_id']} (pos: \"{$example['current_pos']}\") comes before Card #{$example['next_id']} (pos: \"{$example['next_pos']}\")");
            }
            $this->newLine();
        }

        if ($issue['type'] === 'duplicate') {
            warning("  ⚠️  Found {$issue['count']} duplicate positions in column '{$issue['column']}'");
            $this->line("     ({$issue['unique_positions']} unique position values with duplicates)");
            $this->newLine();
        }

        if ($issue['type'] === 'null') {
            info("  ℹ️  Found {$issue['count']} records with null positions");
            $this->newLine();
        }

        info('  💡 After fixing issues, run: php artisan flowforge:repair-positions');
        $this->newLine();
    }

    private function applyCollationFix(Model $model, string $positionField): void
    {
        $connection = $model->getConnection();
        $driver = $connection->getDriverName();
        $table = $model->getTable();

        $this->line('🔧 Applying collation fix...');

        try {
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$positionField}` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin");
                info('  ✓ Collation updated successfully');
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN \"{$positionField}\" TYPE VARCHAR(255) COLLATE \"C\"");
                info('  ✓ Collation updated successfully');
            } else {
                warning("  ⚠️  Auto-fix not supported for {$driver}. Please run the migration manually.");
            }
        } catch (\Exception $e) {
            error("  ❌ Failed to apply fix: {$e->getMessage()}");
        }
    }
}
