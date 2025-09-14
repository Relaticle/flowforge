<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Concerns\InteractsWithBoard;
use Relaticle\Flowforge\Contracts\HasBoard;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

uses(RefreshDatabase::class);

// Test model
class Task extends Model
{
    protected $fillable = ['title', 'status', 'priority', 'position'];
    protected $table = 'tasks';
}

// Test component with both table and board
class TestPageWithTableAndBoard extends Component implements HasTable, HasBoard
{
    use InteractsWithTable;
    use InteractsWithBoard;

    public function render()
    {
        return '<div>{{ $this->table }} {{ $this->board }}</div>';
    }

    // Page table configuration (separate from board)
    public function table(Table $table): Table
    {
        return $table
            ->query(Task::query())
            ->filters([
                SelectFilter::make('priority')
                    ->options([
                        'high' => 'High Priority',
                        'medium' => 'Medium Priority', 
                        'low' => 'Low Priority',
                    ]),
            ]);
    }

    // Board configuration (should be isolated)
    public function board(Board $board): Board
    {
        return $board
            ->query(Task::query())
            ->searchable(['title'])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                    ]),
            ])
            ->columns([
                Column::make('pending', 'Pending'),
                Column::make('in_progress', 'In Progress'),  
                Column::make('completed', 'Completed'),
            ])
            ->recordTitleAttribute('title')
            ->columnIdentifierAttribute('status')
            ->positionIdentifierAttribute('position');
    }

    protected function getBoardQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return Task::query();
    }
}

beforeEach(function () {
    // Create tasks table
    Schema::create('tasks', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('status');
        $table->string('priority')->nullable();
        $table->string('position')->nullable();
        $table->timestamps();
    });

    // Create test data
    Task::create(['title' => 'Task 1', 'status' => 'pending', 'priority' => 'high']);
    Task::create(['title' => 'Task 2', 'status' => 'in_progress', 'priority' => 'medium']);
    Task::create(['title' => 'Task 3', 'status' => 'completed', 'priority' => 'low']);
});

test('board and table have isolated filter states', function () {
    $component = new TestPageWithTableAndBoard();
    
    // Get the page table (regular table)
    $pageTable = $component->table(Table::make($component));
    
    // Get the board table (isolated)
    $boardTable = $component->getBoardTable();
    
    // Verify they have different filter configurations
    $pageFilters = $pageTable->getFilters();
    $boardFilters = $boardTable->getFilters();
    
    expect($pageFilters)->toHaveKey('priority');
    expect($pageFilters)->not()->toHaveKey('status');
    
    expect($boardFilters)->toHaveKey('status');
    expect($boardFilters)->not()->toHaveKey('priority');
});

test('board search is isolated from page table', function () {
    $component = new TestPageWithTableAndBoard();
    
    // Set page table search
    $component->tableSearch = 'page search';
    
    // Set board search
    $component->boardTableSearch = 'board search';
    
    // Verify they are independent
    expect($component->tableSearch)->toBe('page search');
    expect($component->boardTableSearch)->toBe('board search');
    expect($component->getBoardTableSearch())->toBe('board search');
});

test('board filter state is isolated from page table filters', function () {
    $component = new TestPageWithTableAndBoard();
    
    // Set page table filters
    $component->tableFilters = ['priority' => 'high'];
    
    // Set board filters
    $component->boardTableFilters = ['status' => 'pending'];
    
    // Verify they are independent
    expect($component->tableFilters)->toBe(['priority' => 'high']);
    expect($component->boardTableFilters)->toBe(['status' => 'pending']);
});

test('board queries use isolated filter state', function () {
    $component = new TestPageWithTableAndBoard();
    
    // Set board search
    $component->boardTableSearch = 'Task 1';
    
    // Get filtered board query
    $filteredQuery = $component->getFilteredBoardQuery();
    
    // Verify search is applied
    expect($filteredQuery)->not()->toBeNull();
    $results = $filteredQuery->get();
    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('Task 1');
});

test('board renders with isolated filters component', function () {
    $component = new TestPageWithTableAndBoard();
    $board = $component->getBoard();
    
    // Verify board-specific filters view exists
    expect(view()->exists('flowforge::components.board-filters'))->toBeTrue();
    
    // Verify board has its own filter methods
    expect(method_exists($component, 'getBoardTable'))->toBeTrue();
    expect(method_exists($component, 'getBoardTableFiltersForm'))->toBeTrue();
    expect(method_exists($component, 'resetBoardTableFiltersForm'))->toBeTrue();
});