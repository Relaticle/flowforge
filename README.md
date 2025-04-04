# Flowforge - Laravel Filament Kanban Board

Flowforge is a powerful Kanban board package for Laravel Filament 3 that works seamlessly with your existing Eloquent models. This package allows you to transform any model into a Kanban board with minimal configuration, without requiring additional database tables.

> [!IMPORTANT]
> This package is a work in progress and is not yet ready for production use. It is currently in the alpha stage and may have bugs or incomplete 
features.

## Requirements

- PHP 8.3+
- Laravel 11+
- Filament 3.x

## Installation

You can install the package via composer:

```bash
composer require relaticle/flowforge
```

After installing the package, you should publish and run the migrations:

```bash
php artisan flowforge:install
php artisan migrate
```

## Basic Usage

To create a Kanban board for your model, create a new Filament page that extends `KanbanBoardPage`:

```php
<?php

namespace App\Filament\Pages;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Filament\Pages\KanbanBoardPage;

class TasksBoardPage extends KanbanBoardPage
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
    protected static ?string $navigationParentItem = 'Tasks';

    public function mount(): void
    {
        $this
            ->titleField('title')
            ->columnField('status')
            ->descriptionField('description')
            ->orderField('order_column')
            ->columns([
                'todo' => 'To Do',
                'in_progress' => 'In Progress',
                'review' => 'In Review',
                'done' => 'Completed',
            ])
            ->columnColors([
                'todo' => 'gray',
                'in_progress' => 'blue',
                'review' => 'yellow',
                'done' => 'green',
            ]);
    }

    protected function getSubject(): Builder
    {
        return Task::query();
    }
}
```

## Configuration Options

You can customize your Kanban board using these configuration methods:

- `titleField(string)`: Field used for card titles
- `descriptionField(string)`: Field used for card descriptions
- `columnField(string)`: Field used to determine which column a card belongs to
- `orderField(string)`: Field used to maintain card order (requires a sortable model)
- `columns(array)`: Key-value pairs defining columns (key as identifier, value as display label)
- `columnColors(array)`: Key-value pairs defining colors for each column
- `cardLabel(string)`: Custom label for cards (defaults to model name)

## Custom Adapters

For more complex scenarios, you can create a custom adapter by extending `DefaultKanbanAdapter`:

```php
<?php

namespace App\Adapters;

use App\Models\Opportunity;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;

class OpportunitiesKanbanAdapter extends DefaultKanbanAdapter
{
    // Customize forms
    public function getCreateForm(Form $form, mixed $currentColumn): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->required()
                ->placeholder('Enter opportunity title')
                ->columnSpanFull(),
            // Add more form fields as needed
        ]);
    }

    // Custom create logic
    public function createRecord(array $attributes, mixed $currentColumn): ?Model
    {
        // Custom creation logic
        $opportunity = Opportunity::create($attributes);
        
        // Set the column/status value
        $opportunity->status = $currentColumn;
        $opportunity->save();
        
        return $opportunity;
    }
}
```

Then in your page class, override the `getAdapter()` method:

```php
public function getAdapter(): KanbanAdapterInterface
{
    return new OpportunitiesKanbanAdapter($this->getSubject(), $this->config);
}
```

## Integration with Custom Fields

Flowforge can be integrated with custom field systems, as shown in the Opportunities board example:

```php
class OpportunitiesBoardPage extends KanbanBoardPage
{
    public function mount(): void
    {
        $columns = $this->stageCustomField()->options->pluck('name', 'id');
        $columnColors = OpportunityStage::getColors();

        $this
            ->titleField('title')
            ->columnField('status')
            ->descriptionField('description')
            ->orderField('order_column')
            ->cardLabel('Opportunity')
            ->columns($columns->toArray())
            ->columnColors($columns->map(fn($name) => $columnColors[$name] ?? 'gray')->toArray());
    }

    // Use a custom adapter
    public function getAdapter(): KanbanAdapterInterface
    {
        return new OpportunitiesKanbanAdapter(Opportunity::query(), $this->config);
    }
}
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.