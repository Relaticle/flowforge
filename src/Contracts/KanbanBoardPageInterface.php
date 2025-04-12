<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Contracts;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

interface KanbanBoardPageInterface
{
    public function getSubject(): Builder;

//    public function createAction(Action $action): Action;
//
//    public function editAction(Action $action): Action;
}
