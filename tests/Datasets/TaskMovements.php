<?php

use Relaticle\Flowforge\Services\Rank;

dataset('workflow_progressions', [
    'todo_to_in_progress' => ['todo', 'in_progress'],
    'todo_to_completed' => ['todo', 'completed'],
    'in_progress_to_completed' => ['in_progress', 'completed'],
    'in_progress_to_todo' => ['in_progress', 'todo'],
    'completed_to_todo' => ['completed', 'todo'],
    'completed_to_in_progress' => ['completed', 'in_progress'],
]);

dataset('rapid_move_sequences', [
    'indecisive_user' => [['in_progress', 'completed', 'todo']],
    'complex_workflow' => [['completed', 'in_progress', 'todo', 'completed']],
    'back_and_forth' => [['in_progress', 'todo', 'in_progress', 'completed']],
    'same_column_moves' => [['todo', 'todo', 'todo', 'in_progress']],
]);

dataset('board_sizes', [
    'small_team' => 25,
    'medium_team' => 50,
    'large_team' => 100,
    'enterprise' => 250,
]);

dataset('performance_benchmarks', [
    'small_board' => [10, 0.1],    // 10 cards: < 100ms
    'medium_board' => [50, 0.2],   // 50 cards: < 200ms
    'large_board' => [100, 0.3],   // 100 cards: < 300ms
    'huge_board' => [250, 0.5],    // 250 cards: < 500ms
]);

dataset('reordering_patterns', [
    'simple_reorder' => [['after', 'before', 'after']],
    'all_before' => [['before', 'before', 'before']],
    'all_after' => [['after', 'after', 'after']],
    'complex_reorder' => [['after', 'before', 'after', 'before', 'after']],
]);

dataset('cascade_depths', [
    'light_usage' => 5,
    'normal_usage' => 10,
    'heavy_usage' => 15,
    'extreme_usage' => 25,
]);

dataset('team_collaboration_scenarios', [
    'daily_standup' => [
        ['status' => 'in_progress'], // Dev 1 starts task
        ['status' => 'in_progress'], // Dev 2 starts task
        ['status' => 'completed'],   // Dev 3 finishes task
        ['status' => 'todo'],        // PM moves task back
    ],
    'sprint_planning' => [
        ['status' => 'todo'],        // Reprioritize
        ['status' => 'todo'],        // Reprioritize
        ['status' => 'in_progress'], // Start urgent task
        ['status' => 'completed'],   // Complete quick win
    ],
    'crisis_response' => [
        ['status' => 'in_progress'], // All hands on critical bug
        ['status' => 'in_progress'],
        ['status' => 'in_progress'],
        ['status' => 'in_progress'],
    ],
]);

dataset('stress_operation_counts', [
    'light_load' => 50,
    'medium_load' => 100,
    'heavy_load' => 200,
]);

dataset('edge_case_scenarios', [
    'move_all_to_first',
    'circular_moves',
    'mass_revert',
]);

dataset('position_corruption_types', [
    'null_positions',
    'duplicate_positions',
    'invalid_positions',
]);

// Production board state factory
function createProductionBoardState(): array
{
    $rank = Rank::forEmptySequence();
    $tasks = [];

    // Generate tasks with proper Rank positions
    $taskData = [
        ['Fix critical security vulnerability', 'todo', 'high'],
        ['Implement user authentication', 'todo', 'high'],
        ['Add payment processing', 'todo', 'high'],
        ['Build user dashboard', 'todo', 'medium'],
        ['Implement search functionality', 'todo', 'medium'],
        ['Add email notifications', 'todo', 'medium'],
        ['Add dark mode theme', 'todo', 'low'],
        ['Implement keyboard shortcuts', 'todo', 'low'],
        ['Optimize database queries', 'in_progress', 'high'],
        ['Refactor API endpoints', 'in_progress', 'medium'],
        ['Update documentation', 'in_progress', 'low'],
        ['Set up CI/CD pipeline', 'completed', 'high'],
        ['Implement logging system', 'completed', 'medium'],
        ['Create deployment scripts', 'completed', 'medium'],
    ];

    foreach ($taskData as $index => [$title, $status, $priority]) {
        if ($index > 0) {
            $rank = Rank::after($rank);
        }

        $tasks[] = [
            'title' => $title,
            'status' => $status,
            'order_position' => $rank->get(),
            'priority' => $priority,
        ];
    }

    return $tasks;
}

dataset('production_board_states', fn () => [createProductionBoardState()]);
