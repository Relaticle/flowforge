<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Contracts;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;

interface KanbanBoardPageInterface
{
    public function getSubject(): Builder;

    //    public function createAction(Action $action): Action;
    //
    //    public function editAction(Action $action): Action;
    
    /**
     * Define custom actions that will be displayed on each card.
     * 
     * @param ActionGroup $actionGroup The action group to add actions to
     * @return ActionGroup The modified action group
     */
    public function cardActions(ActionGroup $actionGroup): ActionGroup;
}
