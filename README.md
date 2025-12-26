# Flowforge

Transform any Laravel model into a production-ready drag-and-drop Kanban board.

**Works with Filament admin panels and standalone Livewire applications.**

[![Latest Version](https://img.shields.io/packagist/v/relaticle/flowforge.svg?style=for-the-badge)](https://packagist.org/packages/relaticle/flowforge)
[![Total Downloads](https://img.shields.io/packagist/dt/relaticle/flowforge.svg?style=for-the-badge)](https://packagist.org/packages/relaticle/flowforge)
[![PHP 8.3+](https://img.shields.io/badge/php-8.3%2B-blue.svg?style=for-the-badge)](https://php.net)
[![Laravel 12+](https://img.shields.io/badge/laravel-12%2B-red.svg?style=for-the-badge)](https://laravel.com)
[![Tests](https://img.shields.io/github/actions/workflow/status/relaticle/flowforge/run-tests.yml?branch=3.x&style=for-the-badge&label=tests)](https://github.com/relaticle/flowforge/actions)

<div align="center">
<img src="art/preview.png" alt="Flowforge Kanban Board" width="800">
</div>

## Features

- **3 Integration Patterns** - Works with Filament Pages, Resources, or standalone Livewire components
- **Enterprise-Scale Performance** - Cursor-based pagination handles unlimited cards with intelligent loading
- **Rich Card Schemas** - Filament Schema builder creates complex card layouts with forms and components  
- **Smart Position Management** - Advanced ranking algorithm with conflict resolution and repair commands
- **Optimistic UI Experience** - Instant visual feedback with loading states and smooth interactions
- **Native Filament Integration** - Deep table system integration for filters, search, and actions


## Requirements

- **PHP:** 8.3+
- **Laravel:** 12+
- **Livewire:** 3.x+
- **Filament:** 4.x+
- **ext-bcmath:** Required for position calculations
- **Database:** MySQL, PostgreSQL, SQLite, SQL Server, MariaDB


## Installation

```bash
composer require relaticle/flowforge
```

## Usage

**Standalone Livewire** (any Laravel application):
```php
use Relaticle\Flowforge\Concerns\InteractsWithBoard;

class TaskBoard extends Component implements HasBoard
{
    use InteractsWithBoard;
    
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

**Filament Admin Panel**:
```bash
php artisan flowforge:make-board TaskBoard --model=Task
```

**[View Complete Documentation →](https://relaticle.github.io/flowforge/)**

## Our Ecosystem

### FilaForms
[<img src="https://filaforms.app/img/og-image.png" width="419px" />](https://filaforms.app/)

Visual form builder for all your public-facing forms.
[Learn more →](https://filaforms.app)

### Custom Fields
[<img src="https://github.com/Relaticle/custom-fields/raw/2.x/art/preview.png" width="419px" />](https://custom-fields.relaticle.com)

Let users add custom fields to any model without code changes.
[Learn more →](https://custom-fields.relaticle.com/v2/introduction)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

- [Contributing Guidelines](.github/CONTRIBUTING.md) - How to contribute
- [Testing Examples](tests/Feature/) - Production-ready test patterns

## License

MIT License. See [LICENSE.md](LICENSE.md) for details.
