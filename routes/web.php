<?php

use Illuminate\Support\Facades\Route;
use Relaticle\Flowforge\Http\Controllers\KanbanController;

/*
|--------------------------------------------------------------------------
| Flowforge Web Routes
|--------------------------------------------------------------------------
|
| Here are the web routes registered by the Flowforge package.
|
*/

Route::post('/kanban/update-status', [KanbanController::class, 'updateStatus'])
    ->name('flowforge.kanban.update-status');
