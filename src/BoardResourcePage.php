<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Relaticle\Flowforge\Concerns\BaseBoard;
use Relaticle\Flowforge\Contracts\HasBoard;

/**
 * Board page for Filament resource pages.
 * Extends Filament's resource Page class with kanban board functionality.
 *
 * CRITICAL: This class doesn't use InteractsWithRecord trait itself, but child
 * classes might. To handle the trait conflict, we override getDefaultActionRecord()
 * to intelligently route to either board card records or resource records based
 * on whether a recordKey is present in the mounted action context.
 */
abstract class BoardResourcePage extends Page implements HasActions, HasBoard, HasForms
{
    use BaseBoard;

    protected string $view = 'flowforge::filament.pages.board-page';

    /**
     * Override Filament's action resolution to detect and route board actions.
     *
     * This method intercepts the action resolution flow to check if an action
     * is a board action (has recordKey in context). If so, it routes to
     * resolveBoardAction() which properly handles the record resolution,
     * similar to how table actions are handled via resolveTableAction().
     *
     * This mirrors the logic in InteractsWithActions::resolveActions() but adds
     * board action detection.
     *
     * @param  array<array<string, mixed>>  $actions
     * @return array<\Filament\Actions\Action>
     *
     * @throws \Filament\Actions\Exceptions\ActionNotResolvableException
     */
    protected function resolveActions(array $actions): array
    {
        $resolvedActions = [];

        foreach ($actions as $actionNestingIndex => $action) {
            if (blank($action['name'] ?? null)) {
                throw new \Filament\Actions\Exceptions\ActionNotResolvableException('An action tried to resolve without a name.');
            }

            // Check if this is a board CARD action (has recordKey in context)
            // Column actions have 'column' in arguments, not recordKey
            // This detection happens BEFORE schema/table action detection
            $recordKey = $action['context']['recordKey'] ?? null;
            $columnId = $action['arguments']['column'] ?? null;

            // Only route to resolveBoardAction for card actions (not column actions)
            if (filled($recordKey) && blank($columnId)) {
                $resolvedAction = $this->resolveBoardAction($action, $resolvedActions);
            } elseif (filled($action['context']['schemaComponent'] ?? null)) {
                $resolvedAction = $this->resolveSchemaComponentAction($action, $resolvedActions);
            } elseif (filled($action['context']['table'] ?? null)) {
                $resolvedAction = $this->resolveTableAction($action, $resolvedActions);
            } else {
                $resolvedAction = $this->resolveAction($action, $resolvedActions);
            }

            if (! $resolvedAction) {
                continue;
            }

            $resolvedAction->nestingIndex($actionNestingIndex);
            $resolvedAction->boot();

            $resolvedActions[] = $resolvedAction;

            $this->cacheSchema(
                "mountedActionSchema{$actionNestingIndex}",
                $this->getMountedActionSchema($actionNestingIndex, $resolvedAction),
            );
        }

        return $resolvedActions;
    }
}
