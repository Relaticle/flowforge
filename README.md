# Flowforge - Laravel Filament Kanban Board

Flowforge is a lightweight Kanban board package for Filament 3 that works with existing Eloquent models. This package allows developers to transform any model into a Kanban board with minimal configuration, without requiring additional database tables.

> [!IMPORTANT]
> This package is a work in progress and is not yet ready for production use. It is currently in the alpha stage and may have bugs or incomplete features.

## Requirements

- PHP 8.3+
- Laravel 11+
- Filament 3.x

## Installation

```bash
composer require relaticle/flowforge
```

Then publish the configuration and assets:

```bash
php artisan vendor:publish --tag="flowforge-config"
php artisan vendor:publish --tag="flowforge-assets"
```

## Usage

### Quick Start

Create a Filament page that extends `KanbanBoardPage`:

```php
<?php

namespace App\Filament\Pages;

use App\Models\Task;
use Relaticle\Flowforge\Filament\Pages\KanbanBoardPage;

class TaskBoard extends KanbanBoardPage
{
    public function mount(): void
    {
        $this
            ->for(Task::query()->where('user_id', auth()->id()))
            ->columnField('status')
            ->columns([
                'todo' => 'To Do',
                'in_progress' => 'In Progress',
                'done' => 'Completed',
            ])
            ->titleField('title')
            ->descriptionField('description')
            ->withSearchable(['title', 'description']);
    }
}
```

Register the page in your Filament panel:

```php
public function getPages(): array
{
    return [
        'tasks' => \App\Filament\Pages\TaskBoard::class,
    ];
}
```

### Advanced Configuration

The Kanban board is fully configurable:

```php
$this
    ->for(Task::query())
    ->columnField('status')
    ->columns([
        'todo' => 'To Do',
        'in_progress' => 'In Progress',
        'done' => 'Completed',
    ])
    ->titleField('title')
    ->descriptionField('description')
    ->cardAttributes([
        'due_date' => 'Due Date',
        'priority' => 'Priority',
    ])
    ->columnColors([
        'todo' => 'blue',
        'in_progress' => 'yellow',
        'done' => 'green',
    ])
    ->orderField('sort_order')
    ->cardLabel('Task')
    ->pluralCardLabel('Tasks')
    ->createForm(function (Form $form, $activeColumn) {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required(),
                Textarea::make('description'),
                Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ]),
            ]);
    });
```

### Custom Adapter

You can provide a custom adapter for special use cases:

```php
$this
    ->for(Task::query())
    ->withCustomAdapter(new MyCustomAdapter($config))
    ->withAdapterCallback(function (KanbanAdapterInterface $adapter) {
        // Modify the adapter here
        return $adapter;
    });
```

### Multiple Configuration

You can set multiple configuration values at once:

```php
$this
    ->for(Task::query())
    ->withConfiguration([
        'columnField' => 'status',
        'titleField' => 'title',
        'descriptionField' => 'description',
        'columnValues' => [
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'done' => 'Completed',
        ],
    ]);
```

## Architecture

Flowforge uses a clean architecture with three main components:

1. **KanbanConfig**: The immutable configuration object that stores all settings
2. **KanbanAdapterInterface**: The interface that defines data operations
3. **KanbanBoardPage**: The Filament page that provides the UI

### Adapters

The package provides two adapter implementations:

- **EloquentModelAdapter**: For working with model class references (`Task::class`)
- **EloquentQueryAdapter**: For working with query builders (`Task::query()->where(...)`)

The appropriate adapter is automatically selected based on the subject type passed to the `for()` method.

## License

This package is open-source software licensed under the MIT license. 
