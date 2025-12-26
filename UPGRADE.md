# Upgrading from v2.x to v3.x

This guide covers migrating from Flowforge v2.x (Lexorank/string-based positions) to v3.x (DecimalPosition/DECIMAL-based positions).

## Breaking Changes

1. **New Dependency**: `ext-bcmath` PHP extension required
2. **Column Type**: Position column changed from `VARCHAR` to `DECIMAL(20,10)`
3. **Service Removed**: `Rank.php` replaced by `DecimalPosition.php`
4. **Laravel Version**: Now requires Laravel 12+

## Migration Steps

### Step 1: Check PHP Extensions

Ensure the BCMath extension is installed:

```bash
php -m | grep bcmath
```

If not installed, add it to your PHP configuration.

### Step 2: Update Dependencies

```bash
composer require relaticle/flowforge:^3.0
```

### Step 3: Update Position Column

Create a migration to change the position column type:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Option A: Drop and recreate (loses existing positions)
        Schema::table('your_table', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        Schema::table('your_table', function (Blueprint $table) {
            $table->flowforgePositionColumn();
            $table->unique(['status', 'position'], 'unique_position_per_column');
        });

        // Option B: Keep existing order (recommended)
        // See "Preserving Existing Order" section below
    }

    public function down(): void
    {
        Schema::table('your_table', function (Blueprint $table) {
            $table->dropUnique('unique_position_per_column');
            $table->dropColumn('position');
        });

        Schema::table('your_table', function (Blueprint $table) {
            $table->string('position')->nullable();
        });
    }
};
```

### Step 4: Regenerate Positions

After migration, regenerate all positions:

```bash
php artisan flowforge:repair-positions
```

Select the **"regenerate"** strategy when prompted.

### Step 5: Update Code References

Replace all references to the old `Rank` service:

```diff
- use Relaticle\Flowforge\Services\Rank;
+ use Relaticle\Flowforge\Services\DecimalPosition;

// Empty column position
- $position = Rank::forEmptySequence()->get();
+ $position = DecimalPosition::forEmptyColumn();

// Position after another
- $position = Rank::after($lastRank)->get();
+ $position = DecimalPosition::after($lastPosition);

// Position before another
- $position = Rank::before($nextRank)->get();
+ $position = DecimalPosition::before($nextPosition);

// Position between two
- $position = Rank::betweenRanks($prevRank, $nextRank)->get();
+ $position = DecimalPosition::between($afterPos, $beforePos);
```

### Step 6: Verify Data Integrity

Run the diagnose command to check for issues:

```bash
php artisan flowforge:diagnose-positions \
    --model=App\\Models\\YourModel \
    --column=status \
    --position=position
```

## Preserving Existing Order

If you need to preserve existing card order during migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Relaticle\Flowforge\Services\DecimalPosition;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add new decimal column
        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('position_new', 20, 10)->nullable();
        });

        // 2. Convert existing positions maintaining order
        $statuses = DB::table('tasks')->distinct()->pluck('status');

        foreach ($statuses as $status) {
            $tasks = DB::table('tasks')
                ->where('status', $status)
                ->orderBy('position')  // Original string order
                ->get();

            $lastPosition = null;
            foreach ($tasks as $task) {
                $newPosition = $lastPosition === null
                    ? DecimalPosition::forEmptyColumn()
                    : DecimalPosition::after($lastPosition);

                DB::table('tasks')
                    ->where('id', $task->id)
                    ->update(['position_new' => $newPosition]);

                $lastPosition = $newPosition;
            }
        }

        // 3. Swap columns
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->renameColumn('position_new', 'position');
            $table->unique(['status', 'position'], 'unique_position_per_column');
        });
    }
};
```

## New Features in v3.x

### Jitter for Concurrent Safety

Each position calculation now includes ±5% random jitter, preventing collisions when multiple users move cards simultaneously.

### Auto-Rebalancing

When gaps between positions fall below `0.0001`, positions are automatically redistributed with `65535` spacing.

### Retry Mechanism

Unique constraint violations trigger automatic retry with exponential backoff (50ms, 100ms, 200ms).

### New Artisan Commands

- `flowforge:diagnose-positions` - Check for position issues
- `flowforge:rebalance-positions` - Redistribute positions evenly
- `flowforge:repair-positions` - Interactive repair wizard

## API Changes

### DecimalPosition Methods

| v2.x (Rank) | v3.x (DecimalPosition) | Notes |
|-------------|------------------------|-------|
| `Rank::forEmptySequence()` | `DecimalPosition::forEmptyColumn()` | Returns string directly |
| `Rank::after($rank)` | `DecimalPosition::after($position)` | Returns string directly |
| `Rank::before($rank)` | `DecimalPosition::before($position)` | Returns string directly |
| `Rank::betweenRanks($a, $b)` | `DecimalPosition::between($a, $b)` | Includes jitter |
| `$rank->get()` | N/A | No longer needed, returns string |

### Blueprint Macro

The `flowforgePositionColumn()` macro now creates `DECIMAL(20,10)` instead of `VARCHAR`:

```php
// v2.x created:
$table->string('position')->nullable()->collation('utf8mb4_bin');

// v3.x creates:
$table->decimal('position', 20, 10)->nullable();
```

## Troubleshooting

### BCMath Not Found

```
Error: Call to undefined function bcadd()
```

Install the BCMath extension:
- **Ubuntu/Debian**: `sudo apt-get install php-bcmath`
- **macOS (Homebrew)**: Usually included, check `php -m | grep bcmath`
- **Windows**: Enable in php.ini: `extension=bcmath`

### Position Collisions

If you see unique constraint violations after migration:

```bash
php artisan flowforge:repair-positions
# Select "regenerate" strategy
```

### Order Not Preserved

If card order changed after migration, the original string positions may not have been sorted correctly. Run:

```bash
php artisan flowforge:repair-positions
# Select "regenerate" strategy
```

This will reassign positions based on current database order.
