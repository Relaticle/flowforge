# Flowforge

**Transform any Laravel model into a drag-and-drop Kanban board in minutes.**

[![Latest Version](https://img.shields.io/packagist/v/relaticle/flowforge.svg?style=for-the-badge)](https://packagist.org/packages/relaticle/flowforge)
[![Total Downloads](https://img.shields.io/packagist/dt/relaticle/flowforge.svg?style=for-the-badge)](https://packagist.org/packages/relaticle/flowforge)
[![PHP 8.3+](https://img.shields.io/badge/php-8.3%2B-blue.svg?style=for-the-badge)](https://php.net)
[![Filament 4](https://img.shields.io/badge/filament-4.x-purple.svg?style=for-the-badge)](https://filamentphp.com)
[![Tests](https://img.shields.io/github/actions/workflow/status/relaticle/flowforge/run-tests.yml?branch=2.x&style=for-the-badge&label=tests)](https://github.com/relaticle/flowforge/actions)

<div align="center">
<img src="art/preview.png" alt="Flowforge Kanban Board" width="800">
</div>

## Why Flowforge?

✅ **Works with existing models** - No new tables or migrations required  
🚀 **2-minute setup** - From installation to working board  
🎯 **Filament-native** - Integrates seamlessly with your admin panel

---

> **Note:** For Filament v3 compatibility, use version 1.x of this package.
> 
> **⚠️ Beta Warning:** This is a beta version (2.x) and may contain breaking changes.

## Quick Start

Install and create your first Kanban board:

```bash
composer require relaticle/flowforge
php artisan flowforge:make-board TaskBoard --model=Task
```

That's it! Add the generated page to your Filament panel and you have a working Kanban board.

<details>
<summary>📋 <strong>Show complete example</strong></summary>

```php
<?php

namespace App\Filament\Pages;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;

class TaskBoardPage extends BoardPage
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    public function getEloquentQuery(): Builder
    {
        return Task::query();
    }

    public function board(Board $board): Board
    {
        return $board
            ->query($this->getEloquentQuery())
            ->recordTitleAttribute('title')
            ->columnIdentifier('status')
            ->reorderBy('order_column')
            ->columns([
                Column::make('todo')->label('To Do')->color('gray'),
                Column::make('in_progress')->label('In Progress')->color('blue'),
                Column::make('completed')->label('Completed')->color('green'),
            ]);
    }
}
```
</details>

---

## Requirements

- **PHP:** 8.3+
- **Laravel:** 11+
- **Filament:** 4.x

---

## Features

| Feature | Description |
|---------|-------------|
| 🔄 **Model Agnostic** | Works with any Eloquent model |
| 🏗️ **No New Tables** | Uses your existing database structure |
| 🖱️ **Drag & Drop** | Intuitive card movement between columns |
| ⚡ **Minimal Setup** | 2 methods = working board |
| 🎨 **Customizable** | Colors, properties, actions |
| 📱 **Responsive** | Works on all screen sizes |
| 🔍 **Built-in Search** | Find cards instantly |

---

## Installation & Setup

### 1. Install the Package

```bash
composer require relaticle/flowforge
```

### 2. Prepare Your Model

Your model needs these fields:
- **Title field** (e.g., `title`, `name`)
- **Status field** (e.g., `status`, `state`) 
- **Order field** (e.g., `order_column`) - for drag & drop

**Example migration:**
```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('status')->default('todo');
    $table->integer('order_column')->nullable();
    $table->timestamps();
});
```

### 3. Generate Board Page

```bash
php artisan flowforge:make-board TaskBoard --model=Task
```

### 4. Register with Filament

```php
// app/Providers/Filament/AdminPanelProvider.php
->pages([
    App\Filament\Pages\TaskBoardPage::class,
])
```

**Done!** Visit your Filament panel to see your new Kanban board.

---

## Configuration Examples

### Basic Read-Only Board
Perfect for dashboards and overview pages:

```php
public function board(Board $board): Board
{
    return $board
        ->query($this->getEloquentQuery())
        ->recordTitleAttribute('title')
        ->columnIdentifier('status')
        ->columns([
            Column::make('backlog')->label('Backlog'),
            Column::make('active')->label('Active'),
            Column::make('done')->label('Done')->color('green'),
        ]);
}
```

### Interactive Board with Actions
Add create and edit capabilities:

```php
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

public function board(Board $board): Board
{
    return $board
        ->query($this->getEloquentQuery())
        ->recordTitleAttribute('title')
        ->columnIdentifier('status')
        ->reorderBy('order_column')
        ->columns([
            Column::make('todo')->label('To Do')->color('gray'),
            Column::make('in_progress')->label('In Progress')->color('blue'),
            Column::make('completed')->label('Completed')->color('green'),
        ])
        ->columnActions([
            CreateAction::make('create')
                ->label('Add Task')
                ->icon('heroicon-o-plus')
                ->model(Task::class)
                ->form([
                    TextInput::make('title')->required(),
                    Select::make('priority')
                        ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                        ->default('medium'),
                ])
                ->mutateFormDataUsing(function (array $data, string $columnId): array {
                    $data['status'] = $columnId;
                    return $data;
                }),
        ])
        ->cardActions([
            EditAction::make('edit')->model(Task::class),
            DeleteAction::make('delete')->model(Task::class),
        ])
        ->cardAction('edit'); // Make cards clickable
}
```

### Advanced Board with Schema
Use Filament's Schema system for rich card content:

```php
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

public function board(Board $board): Board
{
    return $board
        ->query($this->getEloquentQuery())
        ->recordTitleAttribute('title')
        ->columnIdentifier('status')
        ->cardSchema(fn (Schema $schema) => $schema
            ->components([
                TextEntry::make('priority')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'success',
                        default => 'gray',
                    }),
                TextEntry::make('due_date')
                    ->date()
                    ->icon('heroicon-o-calendar'),
                TextEntry::make('assignee.name')
                    ->icon('heroicon-o-user')
                    ->placeholder('Unassigned'),
            ])
        )
        ->columns([...]);
}
```

---

## API Reference

### Board Configuration Methods

| Method | Description | Required |
|--------|-------------|----------|
| `query(Builder\|Closure)` | Set the data source | ✅ |
| `recordTitleAttribute(string)` | Field used for card titles | ✅ |
| `columnIdentifier(string)` | Field that determines column placement | ✅ |
| `columns(array)` | Define board columns | ✅ |
| `reorderBy(string, string)` | Enable drag & drop with field and direction | |
| `cardSchema(Closure)` | Configure card content with Filament Schema | |
| `cardActions(array)` | Actions for individual cards | |
| `columnActions(array)` | Actions for column headers | |
| `cardAction(string)` | Default action when cards are clicked | |
| `searchable(array)` | Enable search across specified fields | |

### Livewire Methods (Available in your BoardPage)

| Method | Description | Usage |
|--------|-------------|-------|
| `updateRecordsOrderAndColumn(string, array)` | Handle drag & drop updates | Automatic |
| `loadMoreItems(string, ?int)` | Load more cards for pagination | Automatic |
| `getBoardRecord(int\|string)` | Get single record by ID | Manual |
| `getBoardColumnRecords(string)` | Get all records for a column | Manual |
| `getBoardColumnRecordCount(string)` | Count records in a column | Manual |

### Available Colors

`gray`, `red`, `orange`, `yellow`, `green`, `blue`, `indigo`, `purple`, `pink`

---

## Troubleshooting

### 🔧 Cards not draggable
**Cause:** Missing order column or reorderBy configuration
**Solution:**
1. Add integer column to your migration: `$table->integer('order_column')->nullable();`
2. Add `->reorderBy('order_column')` to your board configuration
3. Ensure your model's `$fillable` includes the order column

### 📭 Empty board showing
**Cause:** Query returns no results or status field mismatch
**Debug steps:**
1. Check query: `dd($this->getEloquentQuery()->get());`
2. Verify status values match column names exactly
3. Check database field type (string vs enum)

### ❌ Actions not working
**Cause:** Missing Filament traits or action configuration
**Solution:**
1. Ensure your BoardPage implements `HasActions`, `HasForms`
2. Use these traits in your class:
```php
use InteractsWithActions;
use InteractsWithForms;
use InteractsWithBoard;
```
3. Configure actions properly with `->model(YourModel::class)`

### 🔄 Drag & drop updates not saving
**Cause:** Missing primary key handling or invalid field names
**Solution:**
1. Ensure your model uses standard primary key or override `getKeyName()`
2. Check status field accepts the column identifier values
3. Verify order column exists and is fillable

### 💥 "No default Filament panel" error
**Cause:** Missing panel configuration in tests/development
**Solution:** Add to your panel provider:
```php
Panel::make()->default()->id('admin')
```

### 🎨 Styling not loading
**Cause:** Assets not built or registered
**Solution:**
1. Run `npm run build` to compile assets
2. Ensure Filament can load the assets with proper permissions

## Real-World Examples

### Complete Task Management Board

```php
<?php

namespace App\Filament\Pages;

use App\Models\Task;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;

class TaskBoardPage extends BoardPage
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
    protected static ?string $navigationLabel = 'Task Board';

    public function getEloquentQuery(): Builder
    {
        return Task::query()->with('assignee');
    }

    public function board(Board $board): Board
    {
        return $board
            ->query($this->getEloquentQuery())
            ->recordTitleAttribute('title')
            ->columnIdentifier('status')
            ->reorderBy('order_column', 'desc')
            ->searchable(['title', 'description', 'assignee.name'])
            ->columns([
                Column::make('todo')->label('📋 To Do')->color('gray'),
                Column::make('in_progress')->label('🔄 In Progress')->color('blue'),
                Column::make('review')->label('👁️ Review')->color('purple'),
                Column::make('completed')->label('✅ Completed')->color('green'),
            ])
            ->cardSchema(fn (Schema $schema) => $schema
                ->components([
                    TextEntry::make('priority')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'high' => 'danger',
                            'medium' => 'warning',
                            'low' => 'success',
                            default => 'gray',
                        }),
                    TextEntry::make('due_date')
                        ->date()
                        ->icon('heroicon-o-calendar')
                        ->color('orange'),
                    TextEntry::make('assignee.name')
                        ->icon('heroicon-o-user')
                        ->placeholder('Unassigned'),
                ])
            )
            ->columnActions([
                CreateAction::make('create')
                    ->label('Add Task')
                    ->icon('heroicon-o-plus')
                    ->model(Task::class)
                    ->form([
                        TextInput::make('title')->required(),
                        Select::make('priority')
                            ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                            ->default('medium'),
                        DatePicker::make('due_date'),
                    ])
                    ->mutateFormDataUsing(function (array $data, string $columnId): array {
                        $data['status'] = $columnId;
                        return $data;
                    }),
            ])
            ->cardActions([
                EditAction::make('edit')
                    ->model(Task::class)
                    ->form([
                        TextInput::make('title')->required(),
                        Select::make('status')
                            ->options([
                                'todo' => 'To Do',
                                'in_progress' => 'In Progress', 
                                'review' => 'Review',
                                'completed' => 'Completed',
                            ]),
                        Select::make('priority')
                            ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High']),
                        DatePicker::make('due_date'),
                    ]),
                DeleteAction::make('delete')
                    ->model(Task::class)
                    ->requiresConfirmation(),
            ])
            ->cardAction('edit'); // Make cards clickable to edit
    }
}
```

### Required Database Schema

```sql
CREATE TABLE tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    status VARCHAR(255) DEFAULT 'todo',
    order_column INT NULL,  -- Required for drag & drop
    priority VARCHAR(255) DEFAULT 'medium',
    due_date DATE NULL,
    assignee_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### Testing Your Board

```php
// tests/Feature/TaskBoardTest.php
use Livewire\Livewire;

test('task board renders successfully', function () {
    Task::create(['title' => 'Test Task', 'status' => 'todo']);
    
    Livewire::test(TaskBoardPage::class)
        ->assertSuccessful()
        ->assertSee('Test Task')
        ->assertSee('To Do');
});

test('can move tasks between columns', function () {
    $task = Task::create(['title' => 'Test Task', 'status' => 'todo']);
    
    Livewire::test(TaskBoardPage::class)
        ->call('updateRecordsOrderAndColumn', 'completed', [$task->getKey()])
        ->assertSuccessful();
    
    expect($task->fresh()->status)->toBe('completed');
});
```

---

## Need Help?

- 📖 [Documentation](#) (coming soon)
- 🐛 [Report Issues](https://github.com/relaticle/flowforge/issues)
- 💬 [Discussions](https://github.com/relaticle/flowforge/discussions)

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

MIT License. See [LICENSE.md](LICENSE.md) for details.

---

<div align="center">
<p><strong>Built with ❤️ for the Laravel community</strong></p>
</div>
