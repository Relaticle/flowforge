# Flowforge

**Transform any Laravel model into a production-ready drag-and-drop Kanban board.**

[![Latest Version](https://img.shields.io/packagist/v/relaticle/flowforge.svg?style=for-the-badge)](https://packagist.org/packages/relaticle/flowforge)
[![Total Downloads](https://img.shields.io/packagist/dt/relaticle/flowforge.svg?style=for-the-badge)](https://packagist.org/packages/relaticle/flowforge)
[![PHP 8.3+](https://img.shields.io/badge/php-8.3%2B-blue.svg?style=for-the-badge)](https://php.net)
[![Filament 4](https://img.shields.io/badge/filament-4.x-purple.svg?style=for-the-badge)](https://filamentphp.com)
[![Tests](https://img.shields.io/github/actions/workflow/status/relaticle/flowforge/run-tests.yml?branch=2.x&style=for-the-badge&label=tests)](https://github.com/relaticle/flowforge/actions)

<div align="center">
<img src="art/preview.png" alt="Flowforge Kanban Board" width="800">
</div>

## Why Flowforge?

🎯 **3 Integration Patterns** - Filament Pages, Resources, or standalone Livewire  
⚡ **Production-Ready** - Handles 100+ cards per column with intelligent pagination  
🔧 **Zero Configuration** - Works with your existing models and database  
🎨 **Fully Customizable** - Actions, schemas, filters, and themes

---

## 🚀 Quick Start (90 seconds)

### 1. Install Package
```bash
composer require relaticle/flowforge
```

### 2. Include CSS Assets

> [!IMPORTANT]
> If you have not set up a custom theme and are using Filament Panels follow the instructions in the [Filament Docs](https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme) first.

After setting up a custom theme add the plugin's views to your theme css file.

```css
/* In your main CSS file (e.g., resources/css/app.css) */
@source "../../../../vendor/relaticle/flowforge/resources/views/**/*.blade.php";
```

### 3. Add Position Column
```bash
php artisan make:migration add_position_to_tasks_table
```

```php
// migration
Schema::table('tasks', function (Blueprint $table) {
    $table->flowforgePositionColumn('position'); // Handles database-specific collations automatically
});
```

### 3. Generate Board
```bash
php artisan flowforge:make-board TaskBoard --model=Task
```

### 4. Register Page
```php
// AdminPanelProvider.php
->pages([
    App\Filament\Pages\TaskBoard::class,
])
```

**🎉 Done!** Visit your Filament panel to see your Kanban board in action.

### 5. Repair Positions (Optional)
If you need to fix corrupted or missing position data:
```bash
php artisan flowforge:repair-positions
```

---

## 📋 Requirements

- **PHP:** 8.3+
- **Laravel:** 11+  
- **Filament:** 4.x
- **Database:** MySQL, PostgreSQL, SQLite, SQL Server, MariaDB

---

## 🎯 Integration Patterns

<details>
<summary><strong>🔹 Pattern 1: Filament Page (Recommended)</strong></summary>

Perfect for dedicated board pages in your admin panel.

```php
<?php

namespace App\Filament\Pages;

use App\Models\Task;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;

class TaskBoard extends BoardPage
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
    
    public function board(Board $board): Board
    {
        return $board
            ->query(Task::query())
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->columns([
                Column::make('todo')->label('To Do')->color('gray'),
                Column::make('in_progress')->label('In Progress')->color('blue'),
                Column::make('completed')->label('Completed')->color('green'),
            ]);
    }
}
```

**✅ Use when:** You want a standalone Kanban page in your admin panel  
**✅ Benefits:** Full Filament integration, automatic registration, built-in actions
</details>

<details>
<summary><strong>🔹 Pattern 2: Resource Integration</strong></summary>

Integrate with your existing Filament resources. Perfect for campaign management where teams track tasks within campaigns.

```php
<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use App\Models\Campaign;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardResourcePage;
use Relaticle\Flowforge\Column;

class CampaignTaskBoard extends BoardResourcePage
{
    protected static string $resource = CampaignResource::class;
    
    public function board(Board $board): Board
    {
        return $board
            ->query(
                // Get tasks for this specific campaign and current user's team
                $this->getRecord()
                    ->tasks()
                    ->whereHas('team', fn($q) => $q->where('id', auth()->user()->current_team_id))
                    ->getQuery()
            )
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->columns([
                Column::make('backlog')->label('Backlog')->color('gray'),
                Column::make('in_progress')->label('In Progress')->color('blue'),
                Column::make('review')->label('Review')->color('amber'),
                Column::make('completed')->label('Completed')->color('green'),
            ]);
    }
}

// Register in your CampaignResource
public static function getPages(): array
{
    return [
        'index' => Pages\ListCampaigns::route('/'),
        'create' => Pages\CreateCampaign::route('/create'),
        'edit' => Pages\EditCampaign::route('/{record}/edit'),
        'tasks' => Pages\CampaignTaskBoard::route('/{record}/tasks'), // Add this line
    ];
}
```

**✅ Use when:** You want to add Kanban to existing Filament resources  
**✅ Benefits:** Inherits resource permissions, policies, and global scopes
</details>

<details>
<summary><strong>🔹 Pattern 3: Standalone Livewire</strong></summary>

Use outside of Filament or in custom applications.

```php
<?php

namespace App\Livewire;

use App\Models\Task;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Concerns\InteractsWithBoard;
use Relaticle\Flowforge\Contracts\HasBoard;

class TaskBoard extends Component implements HasBoard, HasActions, HasForms
{
    use InteractsWithBoard;
    use InteractsWithActions;
    use InteractsWithForms;

    public function board(Board $board): Board
    {
        return $board
            ->query(Task::query())
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->columns([
                Column::make('todo')->label('To Do')->color('gray'),
                Column::make('in_progress')->label('In Progress')->color('blue'),
                Column::make('completed')->label('Completed')->color('green'),
            ]);
    }

    public function render()
    {
        return view('livewire.task-board');
    }
}
```

```blade
{{-- resources/views/livewire/task-board.blade.php --}}
<div>
    <h1 class="text-2xl font-bold mb-6">Task Board</h1>
    {{ $this->board }}
</div>
```

**✅ Use when:** Building custom interfaces or non-Filament applications  
**✅ Benefits:** Maximum flexibility, custom styling, independent routing
</details>

---

## 🎨 Customization

### Rich Card Content

```php
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

public function board(Board $board): Board
{
    return $board
        ->cardSchema(fn (Schema $schema) => $schema->components([
            TextEntry::make('priority')->badge()->color(fn ($state) => match($state) {
                'high' => 'danger',
                'medium' => 'warning',
                'low' => 'success',
                default => 'gray'
            }),
            TextEntry::make('due_date')->date()->icon('heroicon-o-calendar'),
            TextEntry::make('assignee.name')->icon('heroicon-o-user'),
        ]));
}
```

### Actions and Interactions

<details>
<summary><strong>Column Actions (Create, Bulk Operations)</strong></summary>

```php
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

public function board(Board $board): Board
{
    return $board
        ->columnActions([
            CreateAction::make()
                ->label('Add Task')
                ->model(Task::class)
                ->form([
                    TextInput::make('title')->required(),
                    Select::make('priority')
                        ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                        ->default('medium'),
                ])
                ->mutateFormDataUsing(function (array $data, array $arguments): array {
                    if (isset($arguments['column'])) {
                        $data['status'] = $arguments['column'];
                        $data['position'] = $this->getBoardPositionInColumn($arguments['column']);
                    }
                    return $data;
                }),
        ]);
}
```
</details>

<details>
<summary><strong>Card Actions (Edit, Delete, Custom)</strong></summary>

```php
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

public function board(Board $board): Board
{
    return $board
        ->cardActions([
            EditAction::make()->model(Task::class),
            DeleteAction::make()->model(Task::class),
        ])
        ->cardAction('edit'); // Makes cards clickable
}
```
</details>

### Search and Filtering

<details>
<summary><strong>Advanced Filtering</strong></summary>

```php
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

public function board(Board $board): Board
{
    return $board
        ->searchable(['title', 'description', 'assignee.name'])
        ->filters([
            SelectFilter::make('priority')
                ->options(TaskPriority::class)
                ->multiple(),
            SelectFilter::make('assigned_to')
                ->relationship('assignee', 'name')
                ->searchable()
                ->preload(),
            Filter::make('overdue')
                ->label('Overdue')
                ->query(fn (Builder $query) => $query->where('due_date', '<', now()))
                ->toggle(),
        ]);
}
```
</details>

---

## 🏗️ Database Schema

### Required Fields
```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->string('title');                         // Card title
    $table->string('status');                        // Column identifier
    $table->flowforgePositionColumn();               // Drag-and-drop ordering (handles DB-specific collations)
    $table->timestamps();
});

// Custom column name
$table->flowforgePositionColumn('sort_order');      // Creates 'sort_order' column instead
```

### Database-Specific Collations

The `flowforgePositionColumn()` method automatically applies the correct binary collation for each database:

| Database | Collation | Purpose |
|----------|-----------|---------|
| **MySQL/MariaDB** | `utf8mb4_bin` | Binary comparison by character code values |
| **PostgreSQL** | `C` | Binary byte comparison (POSIX locale) |
| **SQL Server** | `Latin1_General_BIN2` | Unicode code-point comparison |
| **SQLite** | None | Uses `BINARY` collation by default |

These collations ensure consistent fractional ranking behavior across all database systems.

---

## 🧪 Testing

<details>
<summary><strong>Testing Your Boards</strong></summary>

```php
use Livewire\Livewire;

test('task board renders successfully', function () {
    Task::factory()->count(10)->create();
    
    Livewire::test(TaskBoard::class)
        ->assertSuccessful()
        ->assertSee('To Do')
        ->assertSee('In Progress')
        ->assertSee('Done');
});

test('can move tasks between columns', function () {
    $task = Task::factory()->todo()->create();
    
    Livewire::test(TaskBoard::class)
        ->call('moveCard', $task->id, 'in_progress')
        ->assertSuccessful();
    
    expect($task->fresh()->status)->toBe('in_progress');
});
```
</details>

---

## 🚀 Performance Features

- **Intelligent Pagination**: Efficiently handles 100+ cards per column
- **Infinite Scroll**: Smooth loading with 80% scroll threshold
- **Optimistic UI**: Immediate feedback with rollback on errors
- **Position Algorithm**: Fractional ranking prevents database locks
- **Query Optimization**: Cursor-based pagination with relationship eager loading

---

## 🎛️ API Reference

<details>
<summary><strong>Board Configuration</strong></summary>

| Method | Description | Required |
|--------|-------------|----------|
| `query(Builder)` | Set data source | ✅ |
| `columnIdentifier(string)` | Status field name | ✅ |
| `positionIdentifier(string)` | Position field name | ✅ |
| `columns(array)` | Define board columns | ✅ |
| `recordTitleAttribute(string)` | Card title field | |
| `cardSchema(Closure)` | Rich card content | |
| `cardActions(array)` | Card-level actions | |
| `columnActions(array)` | Column-level actions | |
| `searchable(array)` | Enable search | |
| `filters(array)` | Add filters | |
</details>

<details>
<summary><strong>Column Configuration</strong></summary>

```php
Column::make('todo')
    ->label('To Do')
    ->color('gray')        // gray, blue, red, green, amber, purple, pink
    ->icon('heroicon-o-queue-list')
```
</details>

---

## 🐛 Troubleshooting

<details>
<summary><strong>Common Issues & Solutions</strong></summary>

### Cards not draggable
**Cause:** Missing `positionIdentifier` or position column  
**Solution:** Add `->positionIdentifier('position')` and ensure database column exists

### Empty board
**Cause:** Status values don't match column identifiers  
**Debug:** `dd($this->getEloquentQuery()->get())` to verify data

### Actions not working
**Cause:** Missing traits or action configuration  
**Solution:** Ensure your class uses `InteractsWithActions`, `InteractsWithForms`

### New cards appear randomly
**Cause:** Missing position in create actions  
**Solution:** Add `$data['position'] = $this->getBoardPositionInColumn($arguments['column']);`

### Corrupted or missing position data
**Cause:** Database issues, manual edits, or migration problems  
**Solution:** Run `php artisan flowforge:repair-positions` to fix position data
</details>

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

- [Implementation Guide](docs/IMPLEMENTATION_GUIDE.md) - Complete developer guide  
- [Testing Examples](tests/Feature/) - Production-ready test patterns
- [Report Issues](https://github.com/relaticle/flowforge/issues)

---

## 📄 License

MIT License. See [LICENSE.md](LICENSE.md) for details.

---

<div align="center">
<p><strong>Built with ❤️ for the Laravel community</strong></p>
</div>
