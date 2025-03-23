# Flowforge - Kanban Board for Laravel and Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/relaticle/flowforge.svg?style=flat-square)](https://packagist.org/packages/relaticle/flowforge)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/relaticle/flowforge/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/relaticle/flowforge/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/relaticle/flowforge/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/relaticle/flowforge/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/relaticle/flowforge.svg?style=flat-square)](https://packagist.org/packages/relaticle/flowforge)

Flowforge is a lightweight Kanban board package for Laravel 11 and Filament 3 that works with existing Eloquent models. This package allows developers to transform any model with a status field into a Kanban board with minimal configuration, without requiring additional database tables.

## Features

- Simple integration with existing Eloquent models
- Drag-and-drop functionality between columns
- Customizable card appearance and badge colors
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
}
```

### 2. Create a Kanban board page in your Filament resource

Create a new page that extends the `KanbanBoard` class and configure it with all necessary options:

```php
namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Relaticle\Flowforge\Filament\Resources\Pages\KanbanBoard;

class TaskKanbanBoard extends KanbanBoard
{
    protected static string $resource = TaskResource::class;
    
    public function mount(): void
    {
        parent::mount();
        
        $this->statusField('status')
            ->statusValues([
                'backlog' => 'Backlog',
                'todo' => 'To Do',
                'in_progress' => 'In Progress',
                'review' => 'In Review',
                'testing' => 'Testing',
                'done' => 'Done',
            ])
            ->statusColors([
                'backlog' => 'gray',
                'todo' => 'blue',
                'in_progress' => 'yellow',
                'review' => 'purple',
                'testing' => 'cyan',
                'done' => 'green',
            ])
            ->titleAttribute('title')
            ->descriptionAttribute('description')
            ->cardAttributes([
                'priority' => 'Priority',
                'due_date' => 'Due Date',
            ])
            ->createForm(function(Form $form) {
                return $form
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        RichEditor::make('description')
                            ->columnSpan('full'),
                        Select::make('status')
                            ->options([
                                'todo' => 'To Do',
                                'in_progress' => 'In Progress',
                                'review' => 'In Review',
                                'done' => 'Done',
                            ])
                            ->required()
                            ->default($this->activeColumn),
                        Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->default('medium'),
                        DatePicker::make('due_date'),
                    ]);
            })
            ->editForm(function (Form $form) {
                return $form
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        RichEditor::make('description')
                            ->columnSpan('full'),
                        Select::make('status')
                            ->options([
                                'todo' => 'To Do',
                                'in_progress' => 'In Progress',
                                'review' => 'In Review',
                                'done' => 'Done',
                            ])
                            ->required(),
                        Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ]),
                        DatePicker::make('due_date'),
                    ]);
            });
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

## Configuration Options

### Status Badge Colors

You can customize the colors of the count badges in your Kanban board columns using the `statusColors` method. These are the small rounded indicators showing the number of cards in each column.

Available colors:
- default, slate, gray, zinc, neutral, stone
- red, orange, amber, yellow, lime, green
- emerald, teal, cyan, sky, blue, indigo
- violet, purple, fuchsia, pink, rose

```php
public function mount(): void
{
    parent::mount();
    
    $this->statusField('status')
        ->statusValues([
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'review' => 'In Review',
            'done' => 'Done',
        ])
        ->statusColors([
            'todo' => 'blue',
            'in_progress' => 'yellow',
            'review' => 'purple',
            'done' => 'green',
        ]);
}
```

### Customizing Badge Appearance

The badge colors are defined in the CSS file and can be customized by publishing the assets:

```bash
php artisan vendor:publish --tag="flowforge-assets"
```

Then modify the CSS classes in `resources/css/vendor/flowforge/flowforge.css`.

## Alternative Configuration Approaches

While we recommend configuring everything in the Filament page class as shown above, there are alternative ways to provide configuration:

### Method 1: Using DefaultKanbanAdapter

```php
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;

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

### Method 3: Model-based Configuration (Not Recommended)

While it's possible to define configuration in the model, we recommend keeping all Kanban-related configuration in the Filament page for better separation of concerns.

### Customizing Create and Edit Forms

You can customize the forms used for creating and editing cards by implementing custom form methods in your Kanban board page:

```php
use App\Filament\Resources\TaskResource;
use Relaticle\Flowforge\Filament\Resources\Pages\KanbanBoard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;

class TaskKanbanBoard extends KanbanBoard
{
    // ... other configurations
    
    public function mount(): void
    {
        parent::mount();
        
        $this->statusField('status')
            ->statusValues([
                'todo' => 'To Do',
                'in_progress' => 'In Progress',
                'review' => 'In Review',
                'done' => 'Done',
            ])
            // ... other configurations
            ->createForm(function(Form $form) {
                return $form
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        RichEditor::make('description')
                            ->columnSpan('full'),
                        Select::make('status')
                            ->options([
                                'todo' => 'To Do',
                                'in_progress' => 'In Progress',
                                'review' => 'In Review',
                                'done' => 'Done',
                            ])
                            ->required()
                            ->default($this->activeColumn),
                        Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->default('medium'),
                        DatePicker::make('due_date'),
                    ]);
            })
            ->editForm(function (Form $form) {
                return $form
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        RichEditor::make('description')
                            ->columnSpan('full'),
                        Select::make('status')
                            ->options([
                                'todo' => 'To Do',
                                'in_progress' => 'In Progress',
                                'review' => 'In Review',
                                'done' => 'Done',
                            ])
                            ->required(),
                        Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ]),
                        DatePicker::make('due_date'),
                    ]);
            });
    }
}
```

## Advanced Usage

### Standalone Kanban Board Page

You can also create a standalone Kanban board page that's not tied to a specific resource:

```php
namespace App\Filament\Pages;

use App\Models\Task;
use Relaticle\Flowforge\Filament\Pages\KanbanBoardPage;
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;

class TasksKanbanBoard extends KanbanBoardPage
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
    protected static ?string $navigationLabel = 'Tasks Kanban';
    protected static ?string $title = 'Tasks Kanban Board';
    
    public function mount(): void
    {
        parent::mount();
        
        // Direct configuration approach
        $this->adapter = new DefaultKanbanAdapter(
            Task::class,
            'status',
            [
                'backlog' => 'Backlog',
                'todo' => 'To Do',
                'in_progress' => 'In Progress',
                'review' => 'In Review',
                'testing' => 'Testing',
                'done' => 'Done',
            ],
            'title',
            'description',
            [
                'priority' => 'Priority',
                'due_date' => 'Due Date',
            ],
            [
                'backlog' => 'gray',
                'todo' => 'blue',
                'in_progress' => 'yellow',
                'review' => 'purple',
                'testing' => 'cyan',
                'done' => 'green',
            ]
        );
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
