# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Flowforge is a Laravel package that provides Kanban board functionality for Filament admin panels. It allows developers to create drag-and-drop Kanban boards using existing Eloquent models without requiring additional database tables.

## Key Architecture

### Core Components

1. **BoardPage** (`src/BoardPage.php`) - The main abstract class that developers extend to create Kanban boards with advanced action handling capabilities, including column actions and record actions with proper caching.

2. **Board** (`src/Board.php`) - ViewComponent-based board class using traits for column management, actions, properties, and query interactions.

3. **DefaultKanbanAdapter** (`src/Adapters/DefaultKanbanAdapter.php`) - The primary adapter that handles data transformation between Eloquent models and Kanban board representation.

4. **KanbanConfig** (`src/Config/KanbanConfig.php`) - Immutable configuration object that stores board settings like column fields, colors, card attributes, etc.

5. **Column** (`src/Column.php`) - Represents individual board columns with properties like label, color, and visibility settings.

6. **Property** (`src/Property.php`) - Represents card properties that can be displayed with custom labels, colors, and icons.

### Key Traits and Concerns

- **InteractsWithBoard** - Handles board-specific interactions including record movement
- **HasRecords** - Manages record operations and queries
- **HasColumns** - Column management functionality
- **HasActions** - Action system integration
- **InteractsWithKanbanQuery** - Query building and filtering

### Configuration Pattern

The package uses a fluent Board API where you configure boards using a declarative syntax with Column and Property objects:

```php
public function board(Board $board): Board
{
    return $board
        ->query($this->getEloquentQuery())
        ->cardTitle('title')
        ->columnField('status')
        ->columns([
            Column::make('todo')->label('To Do')->color('gray'),
            Column::make('in_progress')->label('In Progress')->color('blue'),
        ])
        ->columnActions([CreateAction::make()->model(Task::class)])
        ->cardProperties([Property::make('title')->label('Task Title')])
        ->cardActions([EditAction::make()->model(Task::class)]);
}
```

## Development Commands

### Testing
```bash
# Run all tests
composer test
# Or
vendor/bin/pest

# Run tests with coverage
composer test-coverage
# Or  
vendor/bin/pest --coverage
```

### Code Quality
```bash
# Run static analysis
composer analyse
# Or
vendor/bin/phpstan analyse

# Format code
composer format
# Or
vendor/bin/pint
```

### Package Discovery
```bash
# Rebuild autoloader and discover packages
composer dump-autoload
```

## Code Generation

The package includes an Artisan command for generating minimal Kanban board pages:

```bash
php artisan flowforge:make-board TasksBoard --model=Task
```

This command generates a minimal read-only board in `app/Filament/Pages/` using the stub file at `stubs/kanban-board-page.stub`.

## Testing Framework

- **Framework**: Pest PHP with Laravel integration
- **Configuration**: `phpunit.xml.dist` with strict settings
- **Test Structure**: Feature tests in `tests/Feature/`, architectural tests in `ArchTest.php`
- **Coverage**: HTML reports generated to `build/coverage/`

## Code Standards

- **PHP Version**: 8.3+
- **Formatting**: Laravel Pint with custom rules in `pint.json`
- **Static Analysis**: PHPStan level 4 with Octane compatibility checks
- **Architecture**: Uses strict type declarations (`declare(strict_types=1)`)

## Key Development Patterns

### Fluent Board API
The main API uses a fluent interface where you configure boards by chaining methods that return the Board instance. This provides a declarative way to set up columns, actions, and properties.

### Component-based Architecture  
Columns and Properties are separate classes that encapsulate their own configuration and behavior, following the component pattern used throughout Filament.

### Trait-based Architecture
Heavy use of traits for mixins of functionality, allowing composition over inheritance.

### Filament Integration
Deep integration with Filament's action system, form builders, and page structures. Supports standard Filament actions like CreateAction, EditAction, and DeleteAction.

### Adapter Pattern
Uses adapters to handle different data sources and transformation requirements.

## Livewire Components

The package includes Livewire components in `src/Livewire/` and corresponding Blade templates in `resources/views/livewire/` for rendering the Kanban board interface.

## Asset Building

Frontend assets are built using:
- PostCSS configuration in `postcss.config.cjs`
- CSS files in `resources/css/`
- JavaScript files in `resources/js/`
- Built assets output to `resources/dist/`