<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Filament\Support\Components\ViewComponent;
use Relaticle\Flowforge\Concerns\BelongsToLivewire;
use Relaticle\Flowforge\Concerns\HasActions;
use Relaticle\Flowforge\Concerns\HasCardAction;
use Relaticle\Flowforge\Concerns\HasColumns;
use Relaticle\Flowforge\Concerns\HasProperties;
use Relaticle\Flowforge\Concerns\InteractsWithKanbanQuery;
use Relaticle\Flowforge\Contracts\HasBoard;

class Board extends ViewComponent
{
    use BelongsToLivewire;
    use HasActions;
    use HasCardAction;
    use HasColumns;
    use HasProperties;
    use InteractsWithKanbanQuery;

    /**
     * @var view-string
     */
    protected string $view = 'flowforge::index';

    protected string $viewIdentifier = 'board';

    protected string $evaluationIdentifier = 'board';

    final public function __construct(HasBoard $livewire)
    {
        $this->livewire($livewire);
    }

    public static function make(HasBoard $livewire): static
    {
        $static = app(static::class, ['livewire' => $livewire]);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Any board-specific setup can go here
    }

    /**
     * Get registered card actions.
     */
    public function getRegisteredCardActions(): array
    {
        return [];
    }

    /**
     * Get a registered card action by name.
     */
    public function getRegisteredCardAction(string $name): ?\Filament\Actions\Action
    {
        return null;
    }

    /**
     * Get view data for the board template.
     */
    public function getViewData(): array
    {
        // Create a simple config object for the view
        $config = new class {
            public function getTitleField(): string { return 'title'; }
            public function getDescriptionField(): string { return 'description'; }
            public function getColumnField(): string { return 'status'; }
            public function getSingularCardLabel(): string { return 'Card'; }
            public function getPluralCardLabel(): string { return 'Cards'; }
        };

        // Create columns data with actual database records
        $columns = [];
        foreach ($this->getColumns() as $column) {
            $columnId = $column->getName();
            
            // Get the actual data from the query (clone for each column)
            $baseQuery = $this->getQuery();
            if ($baseQuery) {
                $statusField = $this->getColumnIdentifierAttribute() ?? 'status';
                $itemsQuery = clone $baseQuery;
                $countQuery = clone $baseQuery;
                
                $items = $itemsQuery->where($statusField, $columnId)->get()->toArray();
                $total = $countQuery->where($statusField, $columnId)->count();
            } else {
                $items = [];
                $total = 0;
            }
            
            $columns[$columnId] = [
                'id' => $columnId,
                'label' => $column->getLabel(),
                'color' => $column->getColor(),
                'items' => $items,
                'total' => $total,
            ];
        }

        return [
            'columns' => $columns,
            'config' => $config,
        ];
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'livewire' => [$this->getLivewire()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
