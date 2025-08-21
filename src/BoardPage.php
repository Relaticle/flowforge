<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;
use Relaticle\Flowforge\Concerns\HasRecords;
use Relaticle\Flowforge\Concerns\InteractsWithBoard;
use Relaticle\Flowforge\Config\KanbanConfig;
use Relaticle\Flowforge\Contracts\HasBoard;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;

abstract class BoardPage extends Page implements HasActions, HasBoard, HasForms
{
    use HasRecords;
    use InteractsWithActions;
    use InteractsWithBoard {
        InteractsWithBoard::moveRecord insteadof HasRecords;
    }
    use InteractsWithForms;

    protected string $view = 'flowforge::filament.pages.board-page';

    protected ?KanbanAdapterInterface $adapter = null;

    /**
     * Cached flat actions for efficient lookup.
     */
    protected array $cachedFlatBoardActions = [];

    public function bootedInteractsWithActions(): void
    {
        // Get or create the board (don't overwrite existing configuration)
        $this->board = $this->getBoard();

        // Set the query on the board if not already set
        if (!$this->board->getQuery()) {
            $this->board->query($this->getEloquentQuery());
        }

        // Recreate adapter fresh (Filament pattern)
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

    protected function cacheBoardAction(Action|ActionGroup $action): void
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
     * Check if an action is a record-based action that shouldn't be used as column action.
     */
    public function isRecordBasedAction(Action|ActionGroup $action): bool
    {
        // ActionGroups themselves are not record-based, only individual actions within them can be
        if ($action instanceof ActionGroup) {
            return false;
        }

        $actionClass = get_class($action);

        return str_contains($actionClass, 'DeleteAction') ||
            str_contains($actionClass, 'EditAction') ||
            str_contains($actionClass, 'ViewAction');
    }

    /**
     * Get processed column actions for a specific column.
     */
    public function getColumnActionsForColumn(string $columnId): array
    {
        $processedActions = [];

        foreach ($this->getBoard()->getColumnActions() as $action) {
            try {
                // Skip record-based actions entirely to prevent null record errors
                if ($this->isRecordBasedAction($action)) {
                    continue;
                }

                $actionClone = $action->getClone();

                // Set livewire context using the proper Livewire component from the kanban board
                $livewireComponent = $this->getLivewire() ?? $this;
                $actionClone->livewire($livewireComponent);

                // Store column context for use in action callbacks
                $actionClone->arguments(['column' => $columnId]);

                // Handle ActionGroup differently
                if ($actionClone instanceof ActionGroup) {
                    // Filter and set context for all actions within the group
                    $validGroupActions = [];
                    foreach ($actionClone->getFlatActions() as $flatAction) {
                        // Skip record-based actions in groups too
                        if ($this->isRecordBasedAction($flatAction)) {
                            continue;
                        }

                        $flatAction->livewire($livewireComponent);
                        $flatAction->arguments(['column' => $columnId]);

                        $this->configureColumnAction($flatAction, $columnId);
                        $validGroupActions[] = $flatAction;
                    }

                    // Only include the group if it has valid actions
                    if (!empty($validGroupActions)) {
                        $processedActions[] = $actionClone;
                    }
                } else {
                    // Handle individual actions
                    $this->configureColumnAction($actionClone, $columnId);

                    if (!$actionClone->isHidden()) {
                        $processedActions[] = $actionClone;
                    }
                }
            } catch (\Exception) {
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
        // Since we filter out record-based actions earlier, this method now only
        // handles actions that are safe to use as column actions

        // Set up column context for the action
        if (method_exists($action, 'arguments')) {
            $action->arguments(['column' => $columnId]);
        }
    }

    /**
     * Get processed record actions for a specific record.
     */
    public function getCardActionsForRecord(array $recordData): array
    {
        $processedActions = [];

        try {
            // Get the record model first
            $recordModel = $this->getAdapter()->getModelById($recordData['id']);

            // If we can't find the record, return empty actions
            if (!$recordModel || !($recordModel instanceof \Illuminate\Database\Eloquent\Model)) {
                return [];
            }

            foreach ($this->getBoard()->getRecordActions() as $action) {
                try {
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

                    // Safely check if action is hidden
                    if (!$actionClone->isHidden()) {
                        $processedActions[] = $actionClone;
                    }
                } catch (\Exception $e) {
                    // Skip actions that can't be properly configured
                    continue;
                }
            }
        } catch (\Exception $e) {
            // If anything goes wrong, return empty actions to prevent errors
            return [];
        }

        return $processedActions;
    }

    abstract public function board(Board $board): Board;

    abstract public function getEloquentQuery(): Builder;

    protected function createAdapter(): KanbanAdapterInterface
    {
        $query = $this->getTableQuery();
        $config = $this->createKanbanConfig();

        return new DefaultKanbanAdapter($query, $config);
    }

    protected function getTableQuery(): Builder
    {
        return $this->getEloquentQuery();
    }

    protected function createKanbanConfig(): KanbanConfig
    {
        // Ensure board is initialized before creating config
        if (!isset($this->board)) {
            $this->board = $this->board(Board::make());
            // Set the query on the board if not already set
            if (!$this->board->getQuery()) {
                $this->board->query($this->getEloquentQuery());
            }
        }

        $board = $this->board;

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

        if ($descriptionAttribute = $board->getDescriptionAttribute()) {
            $configData['descriptionField'] = $descriptionAttribute;
        }

        if ($columnAttribute = $board->getColumnIdentifierAttribute()) {
            $configData['columnField'] = $columnAttribute;
        }

        if ($defaultSort = $board->getDefaultSort()) {
            $configData['orderField'] = $defaultSort['column'];
        }

        // Add card properties to config - store the property instances for formatting
        if ($cardProperties = $board->getCardProperties()) {
            $configData['cardProperties'] = $cardProperties;
        }

        return new KanbanConfig(...$configData);
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
            'adapter' => $this->getAdapter(),
            'pageClass' => static::class,
        ];
    }
}
