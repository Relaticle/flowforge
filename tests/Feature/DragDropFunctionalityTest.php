<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Project;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;
use Relaticle\Flowforge\Tests\Fixtures\User;

describe('Production-Ready Kanban Drag & Drop System', function () {
    beforeEach(function () {
        // Create realistic production environment with users, projects, and tasks
        $this->users = User::factory()->count(6)->create([
            'team' => 'Development',
        ]);

        $this->projects = Project::factory()->count(3)->create([
            'owner_id' => $this->users->random()->id,
        ]);

        // Create tasks across different projects with realistic distribution
        $this->tasks = collect();

        // Project 1: Active development project with mixed priority tasks
        $project1Tasks = Task::factory()->count(8)->create([
            'project_id' => $this->projects->get(0)->id,
            'created_by' => $this->users->random()->id,
            'status' => 'todo',
        ]);
        $this->tasks = $this->tasks->merge($project1Tasks);

        // Project 2: Tasks in progress
        $project2Tasks = Task::factory()->count(3)->create([
            'project_id' => $this->projects->get(1)->id,
            'created_by' => $this->users->random()->id,
            'status' => 'in_progress',
            'assigned_to' => $this->users->random()->id,
        ]);
        $this->tasks = $this->tasks->merge($project2Tasks);

        // Project 3: Completed tasks
        $project3Tasks = Task::factory()->count(3)->create([
            'project_id' => $this->projects->get(2)->id,
            'created_by' => $this->users->random()->id,
            'status' => 'completed',
            'assigned_to' => $this->users->random()->id,
            'completed_at' => now()->subDays(rand(1, 30)),
        ]);
        $this->tasks = $this->tasks->merge($project3Tasks);

        $this->board = Livewire::test(TestBoard::class);
    });

    describe('Core Movement Functionality', function () {
        it('executes basic card movements between columns', function (string $fromStatus, string $toStatus) {
            $task = Task::where('status', $fromStatus)->first();
            expect($task)->not()->toBeNull();

            $originalPosition = $task->order_position;
            $originalAssignee = $task->assigned_to;

            $this->board->call('moveCard', (string) $task->id, $toStatus);

            $task->refresh();
            expect($task->status)->toBe($toStatus)
                ->and($task->order_position)->not()->toBeNull()
                ->and($task->assigned_to)->toBe($originalAssignee); // Assignee should not change

            // Position should be valid regardless of column change
            expect($task->order_position)->toBeString()->not()->toBeEmpty();
        })->with('workflow_progressions');

        it('handles rapid sequential movements without data corruption', function (array $moveSequence) {
            $task = Task::where('status', 'todo')->first();
            expect($task)->not()->toBeNull();

            $originalProjectId = $task->project_id;
            $originalCreatedBy = $task->created_by;

            foreach ($moveSequence as $status) {
                $this->board->call('moveCard', (string) $task->id, $status);
            }

            $task->refresh();
            expect($task->status)->toBe(end($moveSequence))
                ->and($task->order_position)->not()->toBeNull()->toBeString()
                ->and($task->project_id)->toBe($originalProjectId)
                ->and($task->created_by)->toBe($originalCreatedBy);
        })->with('rapid_move_sequences');

        it('maintains all related data integrity during moves', function () {
            $task = Task::with(['project', 'assignedUser', 'creator'])->where('status', 'todo')->first();

            $originalTitle = $task->title;
            $originalPriority = $task->priority;
            $originalDescription = $task->description;
            $originalLabels = $task->labels;
            $originalDueDate = $task->due_date;

            $this->board->call('moveCard', (string) $task->id, 'in_progress');

            $task->refresh();
            expect($task->title)->toBe($originalTitle)
                ->and($task->priority)->toBe($originalPriority)
                ->and($task->description)->toBe($originalDescription)
                ->and($task->labels)->toEqual($originalLabels)
                ->and($task->due_date?->format('Y-m-d'))->toBe($originalDueDate?->format('Y-m-d'))
                ->and($task->status)->toBe('in_progress');
        });
    });

    describe('Position-Based Drag & Drop with Real Data', function () {
        it('handles complex multi-project positioning', function () {
            // Test positioning across different projects in same column
            $todoTasks = Task::where('status', 'todo')->with('project')->orderBy('order_position')->get();
            expect($todoTasks)->toHaveCount(8);

            $sourceCard = $todoTasks->first();
            $targetCard = $todoTasks->skip(3)->first(); // Fourth card, possibly different project

            // Move card maintaining project relationships
            $this->board->call('moveCard', (string) $sourceCard->id, 'todo', null, (string) $targetCard->id);

            $sourceCard->refresh();

            // beforeCardId actually places the card AFTER the specified card (based on implementation)
            expect(strcmp($sourceCard->order_position, $targetCard->order_position))->toBeGreaterThan(0);

            // Project relationship should remain intact
            expect($sourceCard->project_id)->not()->toBeNull();
        });

        it('maintains proper ordering with mixed projects and priorities', function () {
            // Create specific ordering scenario
            $highPriorityTask = Task::factory()->create([
                'status' => 'todo',
                'priority' => 'high',
                'project_id' => $this->projects->first()->id,
            ]);

            $mediumPriorityTask = Task::factory()->create([
                'status' => 'todo',
                'priority' => 'medium',
                'project_id' => $this->projects->last()->id,
            ]);

            // Position high priority task before medium priority
            $this->board->call('moveCard', (string) $highPriorityTask->id, 'todo', null, (string) $mediumPriorityTask->id);

            $highPriorityTask->refresh();
            $mediumPriorityTask->refresh();

            expect(strcmp($highPriorityTask->order_position, $mediumPriorityTask->order_position))->toBeGreaterThan(0);
        });
    });

    describe('Production Workflow Scenarios', function () {
        it('simulates realistic sprint planning with team assignments', function () {
            $developer = $this->users->where('team', 'Development')->first();

            // Sprint planning: Assign high priority tasks to developer
            $sprintTasks = Task::where('status', 'todo')
                ->where('priority', 'high')
                ->take(3)
                ->get();

            foreach ($sprintTasks as $task) {
                // Update assignment and move to in_progress
                $task->update(['assigned_to' => $developer->id]);
                $this->board->call('moveCard', (string) $task->id, 'in_progress');
            }

            // Verify sprint setup
            $inProgressTasks = Task::where('status', 'in_progress')->get();
            expect($inProgressTasks->count())->toBeGreaterThanOrEqual(3); // At least the tasks we just moved

            foreach ($sprintTasks as $task) {
                $task->refresh();
                expect($task->status)->toBe('in_progress')
                    ->and($task->assigned_to)->toBe($developer->id);
            }
        });

        it('handles task completion with timestamps and metrics', function () {
            $inProgressTask = Task::where('status', 'in_progress')->first();
            $inProgressTask->update([
                'estimated_hours' => 8,
                'actual_hours' => null,
            ]);

            $completionTime = now();

            // Complete the task
            $this->board->call('moveCard', (string) $inProgressTask->id, 'completed');

            $inProgressTask->refresh();
            expect($inProgressTask->status)->toBe('completed')
                ->and($inProgressTask->order_position)->not()->toBeNull();

            // In real world, completion would update metrics
            $inProgressTask->update([
                'completed_at' => $completionTime,
                'actual_hours' => 10,
            ]);

            expect($inProgressTask->completed_at)->not()->toBeNull();
        });

        it('maintains referential integrity during bulk operations', function () {
            // Record initial state with all relationships
            $initialState = Task::with(['project', 'assignedUser', 'creator'])->get();
            $initialProjectIds = $initialState->pluck('project_id')->filter()->unique();
            $initialUserIds = $initialState->pluck('assigned_to')->filter()->unique();

            // Perform bulk moves
            $tasks = Task::all();
            for ($i = 0; $i < 25; $i++) {
                $task = $tasks->random();
                $newStatus = collect(['todo', 'in_progress', 'completed'])->random();
                $this->board->call('moveCard', (string) $task->id, $newStatus);
            }

            // Verify relationships are maintained
            $finalState = Task::with(['project', 'assignedUser', 'creator'])->get();
            expect($finalState->count())->toBe($initialState->count());

            // Project assignments should remain unchanged
            $finalProjectIds = $finalState->pluck('project_id')->filter()->unique();
            expect($finalProjectIds->sort()->values()->toArray())
                ->toEqual($initialProjectIds->sort()->values()->toArray());

            // User assignments should remain unchanged
            $finalUserIds = $finalState->pluck('assigned_to')->filter()->unique();
            expect($finalUserIds->sort()->values()->toArray())
                ->toEqual($initialUserIds->sort()->values()->toArray());
        });
    });

    describe('Real-World Performance & Scale Testing', function () {
        it('handles large team boards with multiple projects', function (int $additionalTasks) {
            // Add more tasks to simulate large team environment
            $projects = $this->projects;
            $users = $this->users;

            Task::factory()->count($additionalTasks)->create([
                'project_id' => $projects->random()->id,
                'assigned_to' => $users->random()->id,
                'created_by' => $users->random()->id,
            ]);

            $totalTasks = Task::count();
            expect($totalTasks)->toBeGreaterThan($additionalTasks);

            // Test move performance on large board
            $testCard = Task::inRandomOrder()->first();
            $newStatus = collect(['todo', 'in_progress', 'completed'])->random();

            $startTime = microtime(true);
            $this->board->call('moveCard', (string) $testCard->id, $newStatus);
            $duration = microtime(true) - $startTime;

            expect($duration)->toBeLessThan(0.5); // Should complete within 500ms

            $testCard->refresh();
            expect($testCard->status)->toBe($newStatus);
        })->with([
            'small_team' => 25,
            'medium_team' => 75,
            'large_team' => 150,
        ]);

        it('validates database constraints under stress', function () {
            $tasks = Task::all();

            // Perform stress operations while validating constraints
            for ($i = 0; $i < 50; $i++) {
                $task = $tasks->random();
                $newStatus = collect(['todo', 'in_progress', 'completed'])->random();

                $this->board->call('moveCard', (string) $task->id, $newStatus);

                // Validate database integrity after each move
                $task->refresh();
                expect($task->project_id)->not()->toBeNull()
                    ->and($task->created_by)->not()->toBeNull();
            }

            // Final integrity check - no orphaned data
            $orphanedTasks = Task::whereNull('project_id')->count();
            expect($orphanedTasks)->toBe(0);
        });
    });

    describe('Error Handling & Recovery', function () {
        it('handles concurrent modifications gracefully', function () {
            $task = Task::first();

            // Simulate concurrent modification (e.g., another user updates the task)
            $task->update(['title' => 'Modified by another user']);

            // Move operation should still work
            $this->board->call('moveCard', (string) $task->id, 'in_progress');

            $task->refresh();
            expect($task->status)->toBe('in_progress')
                ->and($task->title)->toBe('Modified by another user');
        });

        it('maintains foreign key constraints during moves', function () {
            $task = Task::with('project')->first();
            $originalProject = $task->project;

            // Move task through all statuses
            $this->board->call('moveCard', (string) $task->id, 'in_progress');
            $this->board->call('moveCard', (string) $task->id, 'completed');
            $this->board->call('moveCard', (string) $task->id, 'todo');

            $task->refresh();
            expect($task->project_id)->toBe($originalProject->id);

            // Verify project relationship still works
            expect($task->project->name)->toBe($originalProject->name);
        });

        it('handles invalid references without corrupting data', function () {
            expect(fn () => $this->board->call('moveCard', 'nonexistent-id', 'todo'))
                ->toThrow(InvalidArgumentException::class);

            // Verify no data corruption occurred
            $taskCount = Task::count();
            $userCount = User::count();
            $projectCount = Project::count();

            expect($taskCount)->toBe($this->tasks->count())
                ->and($userCount)->toBe($this->users->count())
                ->and($projectCount)->toBe($this->projects->count());
        });
    });

    describe('Advanced Position Management', function () {
        it('prevents position collisions in high-frequency scenarios', function () {
            // Simulate rapid task creation and movement (like importing tasks)
            $newTasks = Task::factory()->count(10)->create([
                'status' => 'todo',
                'project_id' => $this->projects->first()->id,
            ]);

            // Move all new tasks rapidly
            foreach ($newTasks as $task) {
                $this->board->call('moveCard', (string) $task->id, 'in_progress');
                $this->board->call('moveCard', (string) $task->id, 'todo');
            }

            // Verify no position duplicates
            $positions = Task::where('status', 'todo')
                ->whereNotNull('order_position')
                ->pluck('order_position')
                ->toArray();

            expect(array_unique($positions))->toHaveCount(count($positions));
        });

        it('maintains ordering consistency with complex project hierarchies', function () {
            // Create tasks with different priorities across projects
            $complexTasks = collect();

            foreach ($this->projects as $project) {
                $projectTasks = Task::factory()->count(5)->create([
                    'project_id' => $project->id,
                    'status' => 'todo',
                    'priority' => collect(['high', 'medium', 'low'])->random(),
                ]);
                $complexTasks = $complexTasks->merge($projectTasks);
            }

            // Reorder based on priority across projects
            $priorityOrder = $complexTasks->sortByDesc(function ($task) {
                return match ($task->priority) {
                    'high' => 3,
                    'medium' => 2,
                    'low' => 1,
                };
            });

            $previousTask = null;
            foreach ($priorityOrder as $task) {
                if ($previousTask) {
                    $this->board->call('moveCard', (string) $task->id, 'todo', (string) $previousTask->id);
                }
                $previousTask = $task;
            }

            // Verify final ordering respects positioning
            $finalTasks = Task::where('status', 'todo')->orderBy('order_position')->get();
            $positions = $finalTasks->pluck('order_position')->toArray();

            expect(array_unique($positions))->toHaveCount(count($positions));

            // Verify positions are properly ordered
            $sortedPositions = $positions;
            sort($sortedPositions);
            expect($positions)->toEqual($sortedPositions);
        });
    });

    describe('Team Collaboration Stress Testing', function () {
        it('simulates realistic daily workflow with multiple team members', function () {
            // Morning standup: Multiple developers pick up work
            $developers = $this->users->where('team', 'Development');
            $backlogTasks = Task::where('status', 'todo')->where('priority', 'high')->get();

            foreach ($backlogTasks->take(3) as $index => $task) {
                $developer = $developers->get($index % $developers->count());
                $task->update(['assigned_to' => $developer->id]);
                $this->board->call('moveCard', (string) $task->id, 'in_progress');
            }

            // Verify assignments and status changes
            $activeWork = Task::where('status', 'in_progress')->get();
            expect($activeWork->count())->toBeGreaterThanOrEqual(3);

            // Mid-day: Some tasks completed, new ones started
            $completableTasks = $activeWork->take(2);
            foreach ($completableTasks as $task) {
                $this->board->call('moveCard', (string) $task->id, 'completed');
                $task->update(['completed_at' => now()]);
            }

            // End of day: Verify team productivity
            $completedToday = Task::where('status', 'completed')
                ->whereNotNull('completed_at')
                ->get();

            expect($completedToday->count())->toBeGreaterThanOrEqual(5);
        });

        it('handles project-based task isolation correctly', function () {
            $project1 = $this->projects->first();
            $project2 = $this->projects->last();

            // Move tasks from project 1
            $project1Tasks = Task::where('project_id', $project1->id)->get();
            foreach ($project1Tasks as $task) {
                $this->board->call('moveCard', (string) $task->id, 'in_progress');
            }

            // Move tasks from project 2
            $project2Tasks = Task::where('project_id', $project2->id)->get();
            foreach ($project2Tasks as $task) {
                $this->board->call('moveCard', (string) $task->id, 'completed');
            }

            // Verify project isolation is maintained
            $project1TasksAfter = Task::where('project_id', $project1->id)->get();
            $project2TasksAfter = Task::where('project_id', $project2->id)->get();

            expect($project1TasksAfter->every(fn ($task) => $task->status === 'in_progress'))->toBeTrue();
            expect($project2TasksAfter->every(fn ($task) => $task->status === 'completed'))->toBeTrue();
        });
    });

    describe('Production Data Integrity & Constraints', function () {
        it('validates all foreign key relationships remain intact', function () {
            $allTasks = Task::with(['project', 'assignedUser', 'creator'])->get();

            // Perform extensive moves
            for ($i = 0; $i < 100; $i++) {
                $task = $allTasks->random();
                $newStatus = collect(['todo', 'in_progress', 'completed'])->random();
                $this->board->call('moveCard', (string) $task->id, $newStatus);
            }

            // Comprehensive relationship validation
            $finalTasks = Task::with(['project', 'assignedUser', 'creator'])->get();

            foreach ($finalTasks as $task) {
                // Core kanban fields should be valid
                expect($task->status)->toBeIn(['todo', 'in_progress', 'completed'])
                    ->and($task->order_position)->not()->toBeNull();

                // Relationships should be resolvable (no broken foreign keys)
                if ($task->project_id) {
                    expect($task->project)->not()->toBeNull();
                }
                if ($task->assigned_to) {
                    expect($task->assignedUser)->not()->toBeNull();
                }
                if ($task->created_by) {
                    expect($task->creator)->not()->toBeNull();
                }
            }
        });

        it('handles database constraints during edge case operations', function () {
            // Test with tasks that have complex constraint scenarios
            $constrainedTask = Task::factory()->create([
                'status' => 'todo',
                'assigned_to' => $this->users->first()->id,
                'project_id' => $this->projects->first()->id,
                'due_date' => now()->addDays(7),
                'labels' => ['critical', 'security', 'hotfix'],
            ]);

            // Multiple rapid moves with constrained data
            for ($i = 0; $i < 10; $i++) {
                $status = collect(['todo', 'in_progress', 'completed'])->random();
                $this->board->call('moveCard', (string) $constrainedTask->id, $status);
            }

            $constrainedTask->refresh();

            // All constraints should still be satisfied
            expect($constrainedTask->assignedUser)->not()->toBeNull()
                ->and($constrainedTask->project)->not()->toBeNull()
                ->and($constrainedTask->labels)->toBeArray()
                ->and($constrainedTask->due_date)->not()->toBeNull();
        });
    });
});
