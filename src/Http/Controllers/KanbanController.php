<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class KanbanController extends Controller
{
    /**
     * Update the status of a card
     * 
     * @param Request $request The request instance
     * @return JsonResponse
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model' => 'required|string',
            'id' => 'required',
            'status' => 'required|string',
        ]);
        
        $modelClass = $validated['model'];
        $id = $validated['id'];
        $status = $validated['status'];
        
        // Check if the model exists and uses the HasKanbanBoard trait
        if (!class_exists($modelClass) || !method_exists($modelClass, 'getKanbanAdapter')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid model or model does not use HasKanbanBoard trait',
            ], 400);
        }
        
        // Find the model instance
        $model = $modelClass::find($id);
        
        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Model not found',
            ], 404);
        }
        
        // Get the adapter and update the status
        $adapter = $model->getKanbanAdapter();
        $success = $adapter->updateStatus($model, $status);
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Status updated successfully' : 'Failed to update status',
        ]);
    }
}
