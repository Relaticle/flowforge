<?php

namespace Relaticle\Flowforge\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Relaticle\Flowforge\Services\PositionRebalancer;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class RebalancePositionsCommand extends Command
{
    protected $signature = 'flowforge:rebalance-positions
                            {--model= : Model class to rebalance (e.g., App\\Models\\Task)}
                            {--column= : Column identifier field}
                            {--position= : Position field name}
                            {--group= : Specific column/group to rebalance}
                            {--dry-run : Show what would be changed without applying}';

    protected $description = 'Rebalance positions in a column to restore optimal gap spacing';

    public function handle(): int
    {
        $this->displayHeader();

        // Get parameters
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

        $rebalancer = new PositionRebalancer;
        $query = $model::query();

        // Check which columns need rebalancing
        $specificGroup = $this->option('group');

        if ($specificGroup) {
            // Rebalance specific group
            $this->rebalanceGroup($rebalancer, $query, $columnField, $specificGroup, $positionField);
        } else {
            // Find and rebalance all groups needing it
            $this->rebalanceAllNeeded($rebalancer, $query, $columnField, $positionField);
        }

        return self::SUCCESS;
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->line('Flowforge Position Rebalancer');
        $this->line('=============================');
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

    private function rebalanceGroup(
        PositionRebalancer $rebalancer,
        $query,
        string $columnField,
        string $groupId,
        string $positionField
    ): void {
        // Get current stats
        $stats = $rebalancer->getGapStatistics($query, $columnField, $groupId, $positionField);

        $this->line("Column '{$groupId}':");
        $this->line("  Records: {$stats['count']}");

        if ($stats['min_gap'] !== null) {
            $this->line("  Min gap: {$stats['min_gap']}");
            $this->line("  Max gap: {$stats['max_gap']}");
            $this->line("  Small gaps: {$stats['small_gaps']}");
        }

        $this->newLine();

        if ($this->option('dry-run')) {
            info("Dry run - would rebalance {$stats['count']} records in column '{$groupId}'");

            return;
        }

        if (! confirm("Rebalance {$stats['count']} records in column '{$groupId}'?", true)) {
            info('Operation cancelled.');

            return;
        }

        $count = $rebalancer->rebalanceColumn($query, $columnField, $groupId, $positionField);
        info("Rebalanced {$count} records in column '{$groupId}'");
    }

    private function rebalanceAllNeeded(
        PositionRebalancer $rebalancer,
        $query,
        string $columnField,
        string $positionField
    ): void {
        $columnsNeedingRebalancing = $rebalancer->findColumnsNeedingRebalancing(
            $query,
            $columnField,
            $positionField
        );

        if ($columnsNeedingRebalancing->isEmpty()) {
            info('No columns need rebalancing. All gap sizes are healthy.');

            return;
        }

        $this->line(sprintf('Found %d column(s) needing rebalancing:', $columnsNeedingRebalancing->count()));
        $this->newLine();

        foreach ($columnsNeedingRebalancing as $columnId) {
            $stats = $rebalancer->getGapStatistics($query, $columnField, (string) $columnId, $positionField);
            $this->line("  - {$columnId}: {$stats['count']} records, {$stats['small_gaps']} small gaps");
        }

        $this->newLine();

        if ($this->option('dry-run')) {
            info('Dry run - no changes applied');

            return;
        }

        if (! confirm('Rebalance all columns listed above?', true)) {
            info('Operation cancelled.');

            return;
        }

        $results = $rebalancer->rebalanceAll($query, $columnField, $positionField);

        $this->newLine();
        info('Rebalancing complete:');
        foreach ($results as $columnId => $count) {
            $this->line("  - {$columnId}: {$count} records rebalanced");
        }
    }
}
