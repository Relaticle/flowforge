<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Filament\Support\Components\ViewComponent;
use Relaticle\Flowforge\Concerns\BelongsToLivewire;
use Relaticle\Flowforge\Concerns\ManagesActions;
use Relaticle\Flowforge\Concerns\ManagesColumns;
use Relaticle\Flowforge\Concerns\HasProperties;
use Relaticle\Flowforge\Concerns\InteractsWithKanbanQuery;
use Relaticle\Flowforge\Contracts\HasBoard;

class Board extends ViewComponent
{
    use BelongsToLivewire;
    use ManagesActions;
    use ManagesColumns;
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
     * Get view data for the board template.
     * Delegates to Livewire component like Filament's Table does.
     */
    public function getViewData(): array
    {
        // Direct delegation to Livewire component (no adapter)
        $livewire = $this->getLivewire();
        
        // Create simple config object
        $config = new class($this) {
            public function __construct(private Board $board) {}
            
            public function getTitleField(): string {
                return $this->board->getCardTitleAttribute() ?? 'title';
            }
            
            public function getDescriptionField(): string {
                return $this->board->getDescriptionAttribute() ?? 'description';
            }
            
            public function getColumnField(): string {
                return $this->board->getColumnIdentifierAttribute() ?? 'status';
            }
            
            public function getSingularCardLabel(): string {
                return 'Card';
            }
            
            public function getPluralCardLabel(): string {
                return 'Cards';
            }
        };

        // Build columns data by delegating to Livewire
        $columns = [];
        foreach ($this->getColumns() as $column) {
            $columnId = $column->getName();
            $columns[$columnId] = [
                'id' => $columnId,
                'label' => $column->getLabel(),
                'color' => $column->getColor(),
                'items' => $this->getBoardRecordsForColumn($columnId),
                'total' => $this->getBoardRecordCountForColumn($columnId),
            ];
        }

        return [
            'columns' => $columns,
            'config' => $config,
        ];
    }

    /**
     * Get board records for a column (delegates to Livewire).
     */
    public function getBoardRecordsForColumn(string $columnId): array
    {
        $livewire = $this->getLivewire();
        
        // Delegate to Livewire component
        if (method_exists($livewire, 'getBoardRecordsForColumn')) {
            return $livewire->getBoardRecordsForColumn($columnId);
        }
        
        // Default implementation
        $query = $this->getQuery();
        if ($query) {
            $statusField = $this->getColumnIdentifierAttribute() ?? 'status';
            return $query->where($statusField, $columnId)->limit(10)->get()->toArray();
        }
        
        return [];
    }

    /**
     * Get board record count for a column (delegates to Livewire).
     */
    public function getBoardRecordCountForColumn(string $columnId): int
    {
        $livewire = $this->getLivewire();
        
        // Delegate to Livewire component
        if (method_exists($livewire, 'getBoardRecordCountForColumn')) {
            return $livewire->getBoardRecordCountForColumn($columnId);
        }
        
        // Default implementation
        $query = $this->getQuery();
        if ($query) {
            $statusField = $this->getColumnIdentifierAttribute() ?? 'status';
            return $query->where($statusField, $columnId)->count();
        }
        
        return 0;
    }

    /**
     * Get card actions for a record (delegates to Livewire).
     */
    public function getCardActionsForRecord(array $record): array
    {
        $livewire = $this->getLivewire();
        
        if (method_exists($livewire, 'getCardActionsForRecord')) {
            return $livewire->getCardActionsForRecord($record);
        }
        
        return $this->getRecordActions();
    }

    /**
     * Get card action for a record (delegates to Livewire).
     */
    public function getCardActionForRecord(array $record): ?string
    {
        $livewire = $this->getLivewire();
        
        if (method_exists($livewire, 'getCardActionForRecord')) {
            return $livewire->getCardActionForRecord($record);
        }
        
        return $this->getCardAction();
    }

    /**
     * Check if card has action (delegates to Livewire).
     */
    public function hasCardAction(array $record): bool
    {
        return $this->getCardActionForRecord($record) !== null;
    }

    /**
     * Get column actions for a column (delegates to Livewire).
     */
    public function getColumnActionsForColumn(string $columnId): array
    {
        $livewire = $this->getLivewire();
        
        if (method_exists($livewire, 'getColumnActionsForColumn')) {
            return $livewire->getColumnActionsForColumn($columnId);
        }
        
        return $this->getColumnActions();
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'livewire' => [$this->getLivewire()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
