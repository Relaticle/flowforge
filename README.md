# Flowforge - Kanban Board for Laravel and Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/relaticle/flowforge.svg?style=flat-square)](https://packagist.org/packages/relaticle/flowforge)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/relaticle/flowforge/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/relaticle/flowforge/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/relaticle/flowforge/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/relaticle/flowforge/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/relaticle/flowforge.svg?style=flat-square)](https://packagist.org/packages/relaticle/flowforge)

Flowforge is a lightweight Kanban board package for Laravel 11 and Filament 3 that works with existing Eloquent models. This package allows developers to transform any model with a status field into a Kanban board with minimal configuration, without requiring additional database tables.

## Features

- Simple integration with existing Eloquent models
- Drag-and-drop functionality between columns
- Customizable card appearance
- Filament 3 integration
- Responsive design
- Dark mode support
- No additional database tables required

## Requirements

- PHP 8.1 or higher
- Laravel 11.x
- Filament 3.x

## Installation

You can install the package via composer:

```bash
composer require relaticle/flowforge
```

The package will automatically register its service provider if you're using Laravel's package auto-discovery.

## Usage

### 1. Add the trait to your model

Add the `HasKanbanBoard` trait to any Eloquent model you want to display on a Kanban board:

```php
use Relaticle\Flowforge\Traits\HasKanbanBoard;

class Task extends Model
{
    use HasFactory;
    use HasKanbanBoard;
    
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
    ];
    
    /**
     * Get the status values for the task.
     *
     * @return array<string, string>
     */
    public static function getStatusOptions(): array
    {
        return [
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'review' => 'In Review',
            'done' => 'Done',
        ];
    }

    /**
     * Get the status colors for the Kanban board.
     *
     * @return array<string, string>
     */
    public function kanbanStatusColors(): array
    {
        return [
            'todo' => 'blue',
            'in_progress' => 'yellow',
            'in_review' => 'purple',
            'done' => 'green',
        ];
    }
}
```

### 2. Create a Kanban board page in your Filament resource

Create a new page that extends the `KanbanBoard` class:

```php
namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Relaticle\Flowforge\Filament\Resources\Pages\KanbanBoard;

class TaskKanbanBoard extends KanbanBoard
{
    protected static string $resource = TaskResource::class;
    
    public function mount(): void
    {
        parent::mount();
        
        $this->statusField('status')
            ->statusValues(Task::getStatusOptions())
            ->statusColors([
                'todo' => 'blue',
                'in_progress' => 'yellow',
                'in_review' => 'purple',
                'done' => 'green',
            ])
            ->titleAttribute('title')
            ->descriptionAttribute('description');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back_to_list')
                ->label('Back to List')
                ->url(fn (): string => TaskResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
```

### 3. Register the Kanban board page in your resource

Add the Kanban board page to your resource's `getPages` method:

```php
public static function getPages(): array
{
    return [
        'index' => Pages\ListTasks::route('/'),
        'create' => Pages\CreateTask::route('/create'),
        'edit' => Pages\EditTask::route('/{record}/edit'),
        'kanban' => Pages\TaskKanbanBoard::route('/kanban'),
    ];
}
```

### 4. Add a link to the Kanban board in your resource's list page

Add a header action to navigate to the Kanban board:

```php
protected function getHeaderActions(): array
{
    return [
        Actions\CreateAction::make(),
        Actions\Action::make('kanban')
            ->label('Kanban Board')
            ->url(fn (): string => TaskResource::getUrl('kanban'))
            ->icon('heroicon-o-view-columns')
            ->color('success'),
    ];
}
```

### Customizing Column Colors

Flowforge allows you to customize the colors of your Kanban board columns. You can choose from a predefined set of Tailwind colors:

- default
- slate
- gray
- zinc
- neutral
- stone
- red
- orange
- amber
- yellow
- lime
- green
- emerald
- teal
- cyan
- sky
- blue
- indigo
- violet
- purple
- fuchsia
- pink
- rose

The colors are defined in the CSS file and can be easily customized by publishing the assets:

```bash
php artisan vendor:publish --tag="flowforge-assets"
```

Then you can modify the CSS classes in `resources/css/vendor/flowforge/flowforge.css`.

To customize column colors, you can use one of the following methods:

### Method 1: Using DefaultKanbanAdapter

```php
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;

// With custom colors
$adapter = new DefaultKanbanAdapter(
    Task::class,
    'status',
    [
        'todo' => 'To Do',
        'in_progress' => 'In Progress',
        'in_review' => 'In Review',
        'done' => 'Done',
    ],
    'title',
    'description',
    [], // card attributes
    [
        'todo' => 'blue',
        'in_progress' => 'yellow',
        'in_review' => 'purple',
        'done' => 'green',
    ]
);
```

### Method 2: Custom Adapter Implementation

If you're creating a custom adapter, implement the `getStatusColors()` method:

```php
public function getStatusColors(): ?array
{
    return [
        'todo' => 'indigo',
        'in_progress' => 'amber', 
        'in_review' => 'violet',
        'done' => 'emerald',
    ];
}
```

### Method 3: Model-based Configuration

Define a `kanbanStatusColors` method in your model:

```php
/**
 * Get the status colors for the Kanban board.
 *
 * @return array<string, string>
 */
public function kanbanStatusColors(): array
{
    return [
        'todo' => 'blue',
        'in_progress' => 'yellow',
        'in_review' => 'purple',
        'done' => 'green',
    ];
}
```

### Method 4: Filament Resource Page

Set the colors directly in your Filament page:

```php
public function mount(): void
{
    parent::mount();
    
    $this->statusField('status')
        ->statusValues(Task::getStatusOptions())
        ->statusColors([
            'todo' => 'blue',
            'in_progress' => 'yellow',
            'in_review' => 'purple',
            'done' => 'green',
        ])
        ->titleAttribute('title')
        ->descriptionAttribute('description');
}
```

If a status doesn't have a color defined, it will use the default styling.

### Customizing Column Status Badges

Flowforge allows you to customize the colors of the count badges in your Kanban board columns. These are the small rounded indicators that show the number of cards in each column. You can choose from a predefined set of Tailwind colors:

- default
- slate
- gray
- zinc
- neutral
- stone
- red
- orange
- amber
- yellow
- lime
- green
- emerald
- teal
- cyan
- sky
- blue
- indigo
- violet
- purple
- fuchsia
- pink
- rose

The badge colors are defined in the CSS file and can be easily customized by publishing the assets:

```bash
php artisan vendor:publish --tag="flowforge-assets"
```

Then you can modify the CSS classes in `resources/css/vendor/flowforge/flowforge.css`.

## Advanced Usage

### Standalone Kanban Board Page

You can also create a standalone Kanban board page that's not tied to a specific resource:

```php
namespace App\Filament\Pages;

use App\Models\Task;
use Relaticle\Flowforge\Filament\Pages\KanbanBoardPage;

class TasksKanbanBoard extends KanbanBoardPage
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
    protected static ?string $navigationLabel = 'Tasks Kanban';
    protected static ?string $title = 'Tasks Kanban Board';
    
    public function mount(): void
    {
        $this->adapter(Task::kanban(
            'status',
            Task::getStatusOptions(),
            'title',
            'description',
            [
                'priority' => 'Priority',
                'due_date' => 'Due Date',
            ]
        ));
        
        parent::mount();
    }
}
```

### Custom Adapter

You can create a custom adapter by implementing the `IKanbanAdapter` interface:

```php
namespace App\Kanban;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

class CustomKanbanAdapter implements IKanbanAdapter
{
    protected Model $model;
    protected string $statusField;
    protected array $statusValues;
    protected string $titleAttribute;
    protected ?string $descriptionAttribute;
    protected array $cardAttributes;
    
    public function __construct(
        Model $model,
        string $statusField,
        array $statusValues,
        string $titleAttribute,
        ?string $descriptionAttribute = null,
        array $cardAttributes = []
    ) {
        $this->model = $model;
        $this->statusField = $statusField;
        $this->statusValues = $statusValues;
        $this->titleAttribute = $titleAttribute;
        $this->descriptionAttribute = $descriptionAttribute;
        $this->cardAttributes = $cardAttributes;
    }
    
    // Implement the interface methods
    // ...
}
```

### Customizing the Card Appearance

You can customize the appearance of the cards by publishing the views:

```bash
php artisan vendor:publish --tag="flowforge-views"
```

## Configuration

You can publish the configuration file with:

```bash
php artisan vendor:publish --tag="flowforge-config"
```

This will publish a `flowforge.php` configuration file to your config directory.

## Styling

The package comes with default styling that integrates well with Filament's design system. You can customize the styling by publishing the CSS:

```bash
php artisan vendor:publish --tag="flowforge-assets"
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [manukminasyan](https://github.com/Relaticle)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
