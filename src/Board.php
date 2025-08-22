<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Filament\Support\Components\ViewComponent;
use Relaticle\Flowforge\Board\Concerns\CanSearchBoardRecords;
use Relaticle\Flowforge\Board\Concerns\HasBoardActions;
use Relaticle\Flowforge\Board\Concerns\HasBoardColumns;
use Relaticle\Flowforge\Board\Concerns\HasBoardRecords;
use Relaticle\Flowforge\Concerns\BelongsToLivewire;
use Relaticle\Flowforge\Concerns\HasProperties;
use Relaticle\Flowforge\Concerns\InteractsWithKanbanQuery;
use Relaticle\Flowforge\Contracts\HasBoard;

class Board extends ViewComponent
{
    use BelongsToLivewire;
    use CanSearchBoardRecords;
    use HasBoardActions;
    use HasBoardColumns;
    use HasBoardRecords;
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
                return $this->board->getRecordTitleAttribute();
            }
            
            public function getDescriptionField(): ?string {
                return $this->board->getRecordDescriptionAttribute();
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

        // Build columns data using new concerns
        $columns = [];
        foreach ($this->getColumns() as $column) {
            $columnId = $column->getName();
            
            // Get formatted records
            $records = $this->getBoardRecords($columnId);
            $formattedRecords = $records->map(fn($record) => $this->formatBoardRecord($record))->toArray();
            
            $columns[$columnId] = [
                'id' => $columnId,
                'label' => $column->getLabel(),
                'color' => $column->getColor(),
                'items' => $formattedRecords,
                'total' => $this->getBoardRecordCount($columnId),
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
