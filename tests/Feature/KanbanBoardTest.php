<?php

namespace Relaticle\Flowforge\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Relaticle\Flowforge\Livewire\KanbanBoard;
use Relaticle\Flowforge\Tests\TestCase;
use Relaticle\Flowforge\Traits\HasKanbanBoard;

// Mock Task model for testing
class MockTask extends Model
{
    use HasKanbanBoard;

    protected $fillable = ['title', 'description', 'status', 'priority', 'due_date'];

    protected $table = 'mock_tasks';

    public $timestamps = false;

    public static function getStatusOptions(): array
    {
        return [
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'review' => 'In Review',
            'done' => 'Done',
        ];
    }
}

class KanbanBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the mock_tasks table
        $this->app['db']->connection()->getSchemaBuilder()->create('mock_tasks', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('todo');
            $table->string('priority')->nullable();
            $table->date('due_date')->nullable();
        });
    }

    /** @test */
    public function it_can_render_kanban_board_component()
    {
        $modelClass = MockTask::class;
        $statusField = 'status';
        $statusValues = MockTask::getStatusOptions();
        $titleAttribute = 'title';
        $descriptionAttribute = 'description';
        $cardAttributes = [
            'priority' => 'Priority',
            'due_date' => 'Due Date',
        ];

        Livewire::test(KanbanBoard::class, [
            'modelClass' => $modelClass,
            'statusField' => $statusField,
            'statusValues' => $statusValues,
            'titleAttribute' => $titleAttribute,
            'descriptionAttribute' => $descriptionAttribute,
            'cardAttributes' => $cardAttributes,
        ])
            ->assertSuccessful()
            ->assertViewHas('columnLabels', $statusValues);
    }

    /** @test */
    public function it_can_update_item_status()
    {
        // Create a task
        $task = MockTask::create([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'todo',
            'priority' => 'high',
            'due_date' => now()->addDays(5),
        ]);

        $modelClass = MockTask::class;
        $statusField = 'status';
        $statusValues = MockTask::getStatusOptions();
        $titleAttribute = 'title';
        $descriptionAttribute = 'description';
        $cardAttributes = [
            'priority' => 'Priority',
            'due_date' => 'Due Date',
        ];

        // Test updating the status
        Livewire::test(KanbanBoard::class, [
            'modelClass' => $modelClass,
            'statusField' => $statusField,
            'statusValues' => $statusValues,
            'titleAttribute' => $titleAttribute,
            'descriptionAttribute' => $descriptionAttribute,
            'cardAttributes' => $cardAttributes,
        ])
            ->call('updateItemStatus', $task->id, 'in_progress')
            ->assertSuccessful();

        // Verify the task status was updated
        $this->assertEquals('in_progress', $task->fresh()->status);
    }
}
