# Flowforge

**Transform any Laravel model into a drag-and-drop Kanban board in minutes.**

[![Latest Version](https://img.shields.io/packagist/v/relaticle/flowforge.svg?style=for-the-badge)](https://packagist.org/packages/relaticle/flowforge)
[![Total Downloads](https://img.shields.io/packagist/dt/relaticle/flowforge.svg?style=for-the-badge)](https://packagist.org/packages/relaticle/flowforge)
[![PHP 8.3+](https://img.shields.io/badge/php-8.3%2B-blue.svg?style=for-the-badge)](https://php.net)
[![Laravel 11+](https://img.shields.io/badge/laravel-11%2B-red.svg?style=for-the-badge)](https://laravel.com)
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

class TaskBoard extends BoardPage
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
            ->columnIdentifier('status')
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
    App\Filament\Pages\TaskBoard::class,
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
        ->cardTitle('title')
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
use Filament\Forms\Components\TextInput;

public function board(Board $board): Board
{
    return $board
        ->query($this->getEloquentQuery())
        ->cardTitle('title')
        ->columnIdentifier('status')
        ->columns([...])
        ->columnActions([
            CreateAction::make()
                ->label('Add Task')
                ->iconButton()
                ->model(Task::class)
                ->schema([
                    TextInput::make('title')->required(),
                ]),
        ])
        ->cardActions([
            EditAction::make()->model(Task::class),
        ]);
}
```

### Advanced Board with Properties
Display additional model attributes:

```php
use Relaticle\Flowforge\Property;

->cardProperties([
    Property::make('description')->label('Description'),
    Property::make('due_date')->label('Due')->color('red'),
    Property::make('assignee.name')->label('Assigned')->icon('heroicon-o-user'),
])
```

---

## API Reference

### Board Configuration Methods

| Method | Description | Required |
|--------|-------------|----------|
| `cardTitle(string)` | Field used for card titles | ✅ |
| `columnIdentifier(string)` | Field that determines column placement | ✅ |
| `columns(array)` | Define board columns | ✅ |
| `query(Builder)` | Set the data source | ✅ |
| `defaultSort(string, ?string)` | Field and optional direction for drag & drop ordering | |
| `cardProperties(array)` | Additional fields to display | |
| `columnActions(array)` | Actions for column headers | |
| `cardActions(array)` | Actions for individual cards | |

### Available Colors

`gray`, `red`, `orange`, `yellow`, `green`, `blue`, `indigo`, `purple`, `pink`

---

## Troubleshooting

<details>
<summary><strong>🔧 Cards not draggable</strong></summary>

**Solution:**
1. Add an integer `order_column` to your model
2. Configure `->defaultSort('order_column')` in your board

```php
->defaultSort('order_column')
```
</details>

<details>
<summary><strong>📭 Empty board</strong></summary>

**Solution:**
1. Ensure your model has records
2. Check status field values match column keys
3. Debug with: `dd($this->getEloquentQuery()->get())`
</details>

<details>
<summary><strong>❌ Create/Edit not working</strong></summary>

**Solution:**
1. Implement `columnActions()` for create functionality
2. Implement `cardActions()` for edit functionality
3. Ensure proper action configuration

```php
->columnActions([CreateAction::make()->model(Task::class)])
```
</details>

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
