# Copilot Instructions for Flowforge

## Project Overview

Flowforge is a Laravel package that transforms any Eloquent model into a production-ready drag-and-drop Kanban board for Filament applications. It provides three integration patterns: Filament Pages, Resources, and standalone Livewire components.

## Technology Stack

- **PHP**: 8.3+ (strict requirement)
- **Laravel**: 11+ with Eloquent ORM
- **Filament**: 4.x (PHP admin panel framework)
- **Livewire**: For reactive components
- **Alpine.js**: For frontend interactions
- **Tailwind CSS**: For styling

## Architecture & Key Concepts

### Core Components

1. **Board**: Main Kanban board component (`src/Board.php`)
2. **Column**: Board columns representing different states (`src/Column.php`)
3. **BoardPage**: Filament page integration (`src/BoardPage.php`)
4. **BoardResourcePage**: Filament resource integration (`src/BoardResourcePage.php`)

### Important Concerns (Traits)

- `InteractsWithBoard`: Main board functionality
- `InteractsWithBoardTable`: Table integration for filters/search
- `InteractsWithIsolatedBoardTable`: Isolated state management (prevents filter conflicts)
- `HasBoardActions`: Board action management
- `HasBoardFilters`: Filter functionality
- `HasBoardRecords`: Record management and queries
- `CanSearchBoardRecords`: Search functionality

### State Management

**CRITICAL**: When working with filters and search functionality:
- Use `InteractsWithIsolatedBoardTable` trait for components that need isolated state
- Board components should use `getBoardTable()` instead of `getTable()` to avoid conflicts
- Implement separate filter state properties (`boardTableFilters`, `boardTableSearch`)
- Use `getFilteredBoardQuery()` for board-specific filtering

## Coding Standards

### PHP Standards
- Follow PSR-12 coding style
- Use Laravel Pint for code formatting: `composer pint`
- Maintain strict type declarations
- Use meaningful method and variable names

### Architecture Patterns
- Use traits for shared functionality
- Implement contracts/interfaces for extensibility
- Follow Filament's component patterns
- Maintain backward compatibility

### Testing Requirements
- **All new features MUST include tests**
- Use Pest PHP testing framework
- Place feature tests in `tests/Feature/`
- Place unit tests in `tests/Unit/`
- Test datasets in `tests/Datasets/`
- Fixtures for test data in `tests/Fixtures/`

### Quality Assurance
- Run full test suite: `composer test` (includes Pint, PHPStan, and Pest)
- Static analysis: `composer analyse` (PHPStan)
- Code formatting: `composer pint`
- Individual tests: `composer pest`

## Development Workflow

### Before Making Changes
1. Run existing tests to ensure baseline: `composer test`
2. Check for related issues/PRs on GitHub
3. Follow the contributing guidelines in `.github/CONTRIBUTING.md`

### When Adding Features
1. **Write tests first** (TDD approach preferred)
2. Implement minimal viable changes
3. Ensure backward compatibility
4. Update documentation if needed
5. Run quality checks: `composer test`

### When Fixing Bugs
1. Write a failing test that reproduces the bug
2. Implement the minimal fix
3. Ensure the test passes
4. Verify no regression in existing tests

## Common Patterns

### Board Implementation
```php
class TaskBoard extends Board
{
    protected static ?string $model = Task::class;
    
    public function board(Board $board): Board
    {
        return $board
            ->columns([
                Column::make('pending', 'Pending'),
                Column::make('in_progress', 'In Progress'),
                Column::make('completed', 'Completed'),
            ])
            ->filters([
                SelectFilter::make('priority')
                    ->options(['high' => 'High', 'medium' => 'Medium', 'low' => 'Low']),
            ]);
    }
}
```

### Isolated State Management
```php
class MyComponent extends Component implements HasTable, HasBoard
{
    use InteractsWithIsolatedBoardTable; // For isolated board state
    
    public function table(Table $table): Table
    {
        return $table->filters([/* page filters */]);
    }
    
    public function board(Board $board): Board
    {
        return $board->filters([/* isolated board filters */]);
    }
}
```

## File Structure

- `src/`: Main package source code
- `src/Concerns/`: Reusable traits and behaviors
- `src/Components/`: Livewire components
- `src/Commands/`: Artisan commands
- `resources/views/`: Blade templates
- `tests/`: Test suites (Pest PHP)
- `docs/`: Documentation (Nuxt.js)

## Dependencies & Integrations

### Required Dependencies
- `filament/filament`: Core Filament framework
- `spatie/laravel-package-tools`: Package development utilities

### Development Dependencies
- `pestphp/pest`: Testing framework
- `larastan/larastan`: Laravel-specific PHPStan rules
- `laravel/pint`: Code style fixer

## Performance Considerations

- Supports 100+ cards per column with intelligent pagination
- Use Eloquent query optimization for large datasets
- Implement proper indexing on position/status columns
- Consider pagination for boards with many records

## Common Issues & Solutions

### Filter/Search State Conflicts
**Problem**: Multiple components on same page sharing filter state
**Solution**: Use `InteractsWithIsolatedBoardTable` trait and board-specific methods

### Performance with Large Datasets
**Problem**: Slow loading with many records
**Solution**: Implement pagination and optimize queries in `getFilteredBoardQuery()`

### Drag & Drop Issues
**Problem**: Position conflicts during reordering
**Solution**: Use database transactions and proper position calculations

## Documentation

- Primary docs: `docs/` directory (Nuxt.js site)
- API reference: Generated from PHPDoc comments
- Examples: `tests/Feature/` directory contains real-world usage patterns

## Release Guidelines

- Follow SemVer 2.0.0
- Update CHANGELOG.md
- Maintain backward compatibility for minor/patch releases
- Use feature flags for experimental functionality

## Support & Community

- Issues: GitHub Issues
- Discussions: GitHub Discussions
- Security: See `.github/SECURITY.md`