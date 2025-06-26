<?php

namespace Relaticle\Flowforge;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;
use Relaticle\Flowforge\Config\KanbanConfig;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;

abstract class BoardPage extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected string $view = 'flowforge::filament.pages.board-page';

    protected ?Board $board = null;

    protected ?KanbanAdapterInterface $adapter = null;

    /**
     * Cached flat actions for efficient lookup.
     */
    protected array $cachedFlatBoardActions = [];

    public function bootedInteractsWithActions(): void
    {
        $this->bootedInteractsWithBoard();
    }

    protected function bootedInteractsWithBoard(): void
    {
        $this->board = $this->makeBoard();
        $this->adapter = $this->createAdapter();
        $this->cacheBoardActions();
    }

    protected function cacheBoardActions(): void
    {
        $this->cachedFlatBoardActions = [];

        // Cache column actions
        foreach ($this->getBoard()->getColumnActions() as $action) {
            $this->cacheBoardAction($action);
        }

        // Cache record actions
        foreach ($this->getBoard()->getRecordActions() as $action) {
            $this->cacheBoardAction($action);
        }
    }

    protected function cacheBoardAction(Action | ActionGroup $action): void
    {
        if ($action instanceof ActionGroup) {
            // Cache all actions within the group using Filament's method
            foreach ($action->getFlatActions() as $flatAction) {
                $this->cacheAction($flatAction);
                $this->cachedFlatBoardActions[$flatAction->getName()] = $flatAction;
            }
        } elseif ($action instanceof Action) {
            // Use Filament's built-in cacheAction for proper setup
            $this->cacheAction($action);
            $this->cachedFlatBoardActions[$action->getName()] = $action;
        }
    }

    public function getBoardAction(string $name): ?Action
    {
        return $this->cachedFlatBoardActions[$name] ?? null;
    }

    public function getCachedBoardActions(): array
    {
        return $this->cachedFlatBoardActions;
    }

    /**
     * Get processed column actions for a specific column.
     */
    public function getColumnActionsForColumn(string $columnId): array
    {
        $processedActions = [];

        foreach ($this->getBoard()->getColumnActions() as $action) {
            try {
                $actionClone = $action->getClone();

                // Set livewire context
                if (method_exists($actionClone, 'livewire')) {
                    $actionClone->livewire($this);
                }

                // Store column context for use in action callbacks
                if (method_exists($actionClone, 'arguments')) {
                    $actionClone->arguments(['column' => $columnId]);
                }

                // Handle ActionGroup differently
                if ($actionClone instanceof ActionGroup) {
                    // Set context for all actions within the group
                    foreach ($actionClone->getFlatActions() as $flatAction) {
                        if (method_exists($flatAction, 'livewire')) {
                            $flatAction->livewire($this);
                        }
                        if (method_exists($flatAction, 'arguments')) {
                            $flatAction->arguments(['column' => $columnId]);
                        }

                        // For record-based actions used as column actions, provide a placeholder
                        $this->configureColumnAction($flatAction, $columnId);
                    }
                } else {
                    // Handle individual actions
                    $this->configureColumnAction($actionClone, $columnId);
                }

                if (! $actionClone->isHidden()) {
                    $processedActions[] = $actionClone;
                }
            } catch (\Exception $e) {
                // Skip actions that can't be properly configured for column context
                continue;
            }
        }

        return $processedActions;
    }

    /**
     * Configure an action for use in column context.
     */
    protected function configureColumnAction(Action $action, string $columnId): void
    {
        // For actions that typically require a record (DeleteAction, EditAction, ViewAction)
        // but are used as column actions, we need to provide a record context
        if (method_exists($action, 'record')) {
            $actionClass = get_class($action);

            // Check if this is a record-based action
            if (str_contains($actionClass, 'DeleteAction') ||
                str_contains($actionClass, 'EditAction') ||
                str_contains($actionClass, 'ViewAction')) {

                // Create a new instance of the model for column actions
                // This provides the necessary context without affecting real data
                $modelClass = $this->getEloquentQuery()->getModel();
                $dummyRecord = new $modelClass;

                // Set the column field to match the current column
                $columnField = $this->getAdapter()->getConfig()->getColumnField();
                if ($columnField) {
                    $dummyRecord->setAttribute($columnField, $columnId);
                }

                $action->record($dummyRecord);
            }
        }
    }

    /**
     * Get processed record actions for a specific record.
     */
    public function getRecordActionsForRecord(array $recordData): array
    {
        $processedActions = [];

        // Get the record model first
        $recordModel = $this->getAdapter()->getModelById($recordData['id']);

        // If we can't find the record, return empty actions
        if (! $recordModel) {
            return [];
        }

        foreach ($this->getBoard()->getRecordActions() as $action) {
            $actionClone = $action->getClone();

            // Set livewire context
            if (method_exists($actionClone, 'livewire')) {
                $actionClone->livewire($this);
            }

            // Handle ActionGroup differently
            if ($actionClone instanceof ActionGroup) {
                // Set context for all actions within the group
                foreach ($actionClone->getFlatActions() as $flatAction) {
                    if (method_exists($flatAction, 'livewire')) {
                        $flatAction->livewire($this);
                    }
                    if (method_exists($flatAction, 'record')) {
                        $flatAction->record($recordModel);
                    }
                }
            } else {
                // Set record context for individual actions
                if (method_exists($actionClone, 'record')) {
                    $actionClone->record($recordModel);
                }
            }

            if (! $actionClone->isHidden()) {
                $processedActions[] = $actionClone;
            }
        }

        return $processedActions;
    }

    protected function makeBoard(): Board
    {
        $board = new Board;
        $this->board($board);

        return $board;
    }

    abstract public function board(Board $board): void;

    abstract public static function getEloquentQuery(): Builder;

    protected function createAdapter(): KanbanAdapterInterface
    {
        $query = $this->getTableQuery();
        $config = $this->createKanbanConfig();

        return new DefaultKanbanAdapter($query, $config);
    }

    protected function getTableQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    protected function createKanbanConfig(): KanbanConfig
    {
        $board = $this->getBoard();

        // Set columns configuration
        $columns = [];
        $columnColors = [];

        foreach ($board->getColumns() as $column) {
            $columns[$column->getName()] = $column->getLabel() ?? ucfirst($column->getName());
            if ($color = $column->getColor()) {
                $columnColors[$column->getName()] = $color;
            }
        }

        // Build config parameters
        $configData = [
            'columnValues' => $columns,
            'columnColors' => $columnColors,
        ];

        if ($titleAttribute = $board->getRecordTitleAttribute()) {
            $configData['titleField'] = $titleAttribute;
        }

        if ($columnAttribute = $board->getColumnIdentifierAttribute()) {
            $configData['columnField'] = $columnAttribute;
        }

        if ($defaultSort = $board->getDefaultSort()) {
            $configData['orderField'] = $defaultSort['column'];
        }

        return new KanbanConfig(...$configData);
    }

    public function getBoard(): Board
    {
        return $this->board ??= $this->makeBoard();
    }

    public function getAdapter(): KanbanAdapterInterface
    {
        return $this->adapter ??= $this->createAdapter();
    }

    public function getPageClass(): string
    {
        return static::class;
    }

    protected function getViewData(): array
    {
        return [
            'adapter' => $this->adapter,
            'pageClass' => static::class,
        ];
    }
}
