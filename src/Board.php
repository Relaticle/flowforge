<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Filament\Support\Components\ViewComponent;
use Relaticle\Flowforge\Concerns\HasActions;
use Relaticle\Flowforge\Concerns\HasCardAction;
use Relaticle\Flowforge\Concerns\HasColumns;
use Relaticle\Flowforge\Concerns\HasProperties;
use Relaticle\Flowforge\Concerns\InteractsWithKanbanQuery;

class Board extends ViewComponent
{
    use HasActions;
    use HasCardAction;
    use HasColumns;
    use HasProperties;
    use InteractsWithKanbanQuery;

    /**
     * @var view-string
     */
    protected string $view = 'flowforge::board';

    protected string $viewIdentifier = 'board';

    protected string $evaluationIdentifier = 'board';

    protected array $filters = [];

    /**
     * @var array<string, \Filament\Actions\Action>
     */
    protected array $registeredCardActions = [];

    final public function __construct()
    {
        // Board should be instantiated via make() method only
    }

    public static function make(): static
    {
        $boardClass = static::class;

        $static = app($boardClass);
        $static->configure();

        return $static;
    }

    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'board' => [$this],
            'filters' => [$this->getFilters()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    /**
     * Override registerCardActionInstance to store Action objects.
     */
    protected function registerCardActionInstance(\Filament\Actions\Action $action, \Illuminate\Database\Eloquent\Model | array $record): void
    {
        \Illuminate\Support\Facades\Log::info('Board::registerCardActionInstance called', [
            'action_name' => $action->getName(),
            'record_id' => is_array($record) ? ($record['id'] ?? 'unknown') : $record->getKey(),
        ]);
        
        $this->registeredCardActions[$action->getName()] = $action;
    }

    /**
     * Get a registered card action by name.
     */
    public function getRegisteredCardAction(string $name): ?\Filament\Actions\Action
    {
        return $this->registeredCardActions[$name] ?? null;
    }

    /**
     * Get all registered card actions.
     *
     * @return array<string, \Filament\Actions\Action>
     */
    public function getRegisteredCardActions(): array
    {
        return $this->registeredCardActions;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Any board-specific setup can go here
    }
}
