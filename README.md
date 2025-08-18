# Flowforge - Powerful Laravel Filament Kanban Board

[![Latest Version on Packagist](https://img.shields.io/packagist/v/relaticle/flowforge.svg?style=flat-square)](https://packagist.org/packages/relaticle/flowforge)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-blue?style=flat-square)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-%3E%3D11.0-red?style=flat-square)](https://laravel.com)
[![Filament Version](https://img.shields.io/badge/filament-3.x-purple?style=flat-square)](https://filamentphp.com)
[![Total Downloads](https://img.shields.io/packagist/dt/relaticle/flowforge.svg?style=flat-square)](https://packagist.org/packages/relaticle/flowforge)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE.md)

Flowforge is a powerful Kanban board package for Laravel Filament 4 that works seamlessly with your existing Eloquent models. This package allows you to transform any model into a Kanban board with minimal configuration, without requiring additional database tables.

![Flowforge Kanban Board in action](art/preview.png)

## 📋 Table of Contents

- [🛠️ Requirements](#️-requirements)
- [🔥 Quick Start](#-quick-start)
- [🌟 Features](#-features)
- [🏗️ Model Preparation](#️-model-preparation)
- [✅ Required Configuration](#-required-configuration)
- [🔄 Interactive Features](#-interactive-features)
- [🧩 Optional Configuration](#-optional-configuration)
- [❓ Troubleshooting](#-troubleshooting)
- [👨‍💻 Contributing](#-contributing)
- [📃 License](#-license)

## 🛠️ Requirements

- PHP 8.3+
- Laravel 11+
- .x

## 🔥 Quick Start

This guide will help you set up a Kanban board for your Laravel application in just a few minutes.

### Step 1: Install Flowforge

```bash
composer require relaticle/flowforge
```

### Step 2: Prepare Your Model

For a basic Kanban board, your model needs:

1. A title field (e.g., `title` or `name`)
2. A status field to determine column placement (e.g., `status` or `state`)
3. An order field for drag & drop (e.g., `order_column`) - required for drag & drop functionality

Example migration:

```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->string('status')->default('todo');
    $table->integer('order_column')->nullable();
    $table->timestamps();
});
```

### Step 3: Generate a Minimal Kanban Board

Use the provided command to create a basic board:

```bash
php artisan flowforge:make-board TasksBoard --model=Task
```

The command will only ask for:
- Board name (if not provided as argument)
- Model name (if not provided with --model flag)

This command will:
1. Create a minimal read-only Kanban board page for your model
2. Set up default columns (todo, in_progress, completed)
3. Generate clean, concise code with no unnecessary comments

### Step 4: Register the Page

Add your new page to Filament's navigation:

```php
// In app/Providers/Filament/AdminPanelProvider.php

public function panel(Panel $panel): Panel
{
    return $panel
        // ... other configuration
        ->pages([
            // ... other pages
            App\Filament\Pages\TasksBoardPage::class,
        ]);
}
```

### Step 5: Visit Your Board

Go to your Filament admin panel and you should see your new Kanban board in the navigation!

The generated board is intentionally minimal - it gives you a working read-only board to start with. When you're ready to add interactive features, you can add `columnActions()` and `cardActions()` to your board configuration following the examples in the [Interactive Features](#-interactive-features) section.

## 🌟 Features

Flowforge offers several powerful features out of the box:

- **Model Agnostic**: Works with any existing Eloquent model
- **No Additional Tables**: Uses your existing models and database structure
- **Read-Only Option**: Create simple view-only boards with minimal code
- **Drag and Drop**: Move cards between columns or reorder them within a column
- **Optional Create/Edit**: Add card creation and editing only if you need it
- **Order Persistence**: Automatically saves card order when dragged
- **Column Colors**: Customize column colors or use auto-generated colors
- **Responsive Design**: Works on all device sizes
- **Customizable Cards**: Display additional model attributes on cards
- **Search Functionality**: Built-in search across specified model fields
- **Integration Support**: Works with other packages like custom fields systems

## 🏗️ Model Preparation

For your Kanban board to be fully functional, your model should have:

1. A field for the card title (e.g., `title`, `name`)
2. A field for the column/status (e.g., `status`, `state`)
3. A field for description (e.g., `description`, `content`) - optional
4. A field for order (e.g., `order_column`, `sort_order`) - required for drag & drop functionality

For drag and drop ordering to work, you can either:

1. Add an integer column to your model migration (as shown in the Quick Start section), or
2. Use a package like [spatie/eloquent-sortable](https://github.com/spatie/eloquent-sortable)

## ✅ Required Configuration

For a functional Kanban board, you only need to implement **two methods**:

### 1. getEloquentQuery() - Provides the data source
```php
public function getEloquentQuery(): Builder
{
    return Task::query();
}
```

### 2. board() - Configures the board using the fluent API
```php
public function board(Board $board): Board
{
    return $board
        ->query($this->getEloquentQuery())
        ->cardTitle('title')        // Required: Field used for card titles
        ->columnField('status')     // Required: Field that determines column placement
        ->columns([                 // Required: Define your columns
            Column::make('todo')->label('To Do')->color('gray'),
            Column::make('in_progress')->label('In Progress')->color('blue'),
            Column::make('completed')->label('Completed')->color('green'),
        ]);
}
```

### Example: Minimal Read-Only Board

Here's a complete example of a minimal read-only board:

```php
<?php

namespace App\Filament\Pages;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;

class TasksBoardPage extends BoardPage
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
            ->cardTitle('title')
            ->columnField('status')
            ->columns([
                Column::make('todo')->label('To Do')->color('gray'),
                Column::make('in_progress')->label('In Progress')->color('blue'),
                Column::make('completed')->label('Completed')->color('green'),
            ]);
    }
}
```

## 🔄 Interactive Features

The Board API provides several optional features to make your Kanban board interactive:

### Column Actions - For creating new cards

Add actions to column headers using the `columnActions()` method:

```php
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;

public function board(Board $board): Board
{
    return $board
        ->query($this->getEloquentQuery())
        ->cardTitle('title')
        ->columnField('status')
        ->columns([...])
        ->columnActions([
            CreateAction::make()
                ->label('Add Task')
                ->iconButton()
                ->icon('heroicon-o-plus')
                ->model(Task::class)
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('description')
                        ->columnSpanFull(),
                ]),
        ]);
}
```

### Card Actions - For editing and deleting cards

Add actions to individual cards using the `cardActions()` method:

```php
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;

->cardActions([
    EditAction::make()
        ->model(Task::class)
        ->schema([
            TextInput::make('title')
                ->required()
                ->columnSpanFull(),
            TextInput::make('description')
                ->columnSpanFull(),
        ])
        ->using(function (Task $record, array $data): Task {
            $record->update($data);
            return $record;
        }),
    DeleteAction::make()
        ->model(Task::class),
])
```

## 🧩 Optional Configuration

The Board API provides these optional configuration methods:

### Card Properties - Display additional information

Use `cardProperties()` to show additional model attributes on cards:

```php
use Relaticle\Flowforge\Property;

->cardProperties([
    Property::make('title')
        ->label('Task Title'),
    Property::make('description')
        ->label('Description'),
    Property::make('due_date')
        ->label('Due Date')
        ->color('red')
        ->icon('heroicon-o-calendar'),
    Property::make('assignee.name')
        ->label('Assigned To')
        ->color('blue')
        ->icon('heroicon-o-user'),
])
```

### Complete Example with All Features

Here's a comprehensive example showing all available configuration options:

```php
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Property;

public function board(Board $board): Board
{
    return $board
        ->query($this->getEloquentQuery())
        ->cardTitle('title')
        ->description('description')
        ->columnField('status')
        ->defaultSort('order_column')
        ->columns([
            Column::make('todo')
                ->label('To Do')
                ->color('gray'),
            Column::make('in_progress')
                ->label('In Progress')
                ->color('blue'),
            Column::make('completed')
                ->label('Completed')
                ->color('green'),
        ])
        ->columnActions([
            CreateAction::make()
                ->label('Add Task')
                ->iconButton()
                ->icon('heroicon-o-plus')
                ->model(Task::class)
                ->schema([
                    TextInput::make('title')->required()->columnSpanFull(),
                    TextInput::make('description')->columnSpanFull(),
                ]),
        ])
        ->cardProperties([
            Property::make('title')->label('Task Title'),
            Property::make('description')->label('Description'),
            Property::make('due_date')
                ->label('Due Date')
                ->color('red')
                ->icon('heroicon-o-calendar'),
        ])
        ->cardActions([
            EditAction::make()
                ->model(Task::class)
                ->schema([
                    TextInput::make('title')->required()->columnSpanFull(),
                    TextInput::make('description')->columnSpanFull(),
                ])
                ->using(function (Task $record, array $data): Task {
                    $record->update($data);
                    return $record;
                }),
            DeleteAction::make()->model(Task::class),
        ]);
}
```

### Available Column Colors

Flowforge uses Tailwind CSS color classes for column styling. The available color options are:

- `white`
- `slate`
- `gray`
- `zinc`
- `neutral`
- `stone`
- `red`
- `orange`
- `amber`
- `yellow`
- `lime`
- `green`
- `emerald`
- `teal`
- `cyan`
- `sky`
- `blue`
- `indigo`
- `violet`
- `purple`
- `fuchsia`
- `pink`
- `rose`

If you don't specify colors, they will be automatically assigned from this palette in a rotating manner.

## Custom Adapters

For complex scenarios (like integration with custom fields), you can create a custom adapter by implementing `KanbanAdapterInterface` or extending `DefaultKanbanAdapter`. This is an advanced feature and only needed for special cases.

## ❓ Troubleshooting

### Common Issues and Solutions

#### Cards not draggable

**Issue**: You can see cards but can't drag them between columns or reorder them.

**Solution**:
1. Ensure your model has an integer field for ordering (e.g., `order_column`)
2. Configure this field with `->defaultSort('order_column')` in your `board()` method
3. Check browser console for JavaScript errors

#### Empty board or missing columns

**Issue**: Your board appears empty or has fewer columns than expected.

**Solution**:
1. Verify your model has records with the status values matching your column keys
2. Check that your `columnField()` matches a real field in your database
3. Use `dd($this->getEloquentQuery()->get())` in your page class to debug your model data

#### Form validation errors

**Issue**: Form submissions fail with validation errors.

**Solution**:
1. Ensure all required model fields are included in your forms
2. Check for any unique constraints in your model
3. Look for mutators or observers that might be affecting the data

#### Can't create or edit cards

**Issue**: No create/edit options appear.

**Solution**:
1. Make sure you've configured `columnActions()` and/or `cardActions()` in your board method
2. Check that you've properly imported and used the Filament Action and Forms classes
3. Verify that you've configured the actions properly in your board configuration

#### Cards not saving when dragged

**Issue**: Cards can be dragged but don't stay in place after reload.

**Solution**:
1. Ensure the `->defaultSort()` method is set in your board configuration
2. Verify your model is properly saving the order value
3. If using Spatie's Eloquent-Sortable, ensure it's correctly configured

### Advanced Troubleshooting

If you're still experiencing issues, try these steps:

1. Enable debug mode in your Laravel application
2. Check Laravel logs for errors
3. Inspect network requests in your browser's developer tools
4. Verify your Filament and Laravel versions match the requirements
5. Try a simpler configuration first, then add complexity

## 👨‍💻 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📃 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
