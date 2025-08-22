<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Examples;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Concerns\InteractsWithBoardFilters;
use Relaticle\Flowforge\Contracts\HasBoard;
use Relaticle\Flowforge\Tests\Fixtures\Task;

/**
 * Example implementation showing how to integrate Filament table filters 
 * into a Flowforge board seamlessly.
 */
class FilterableTaskBoard extends Component implements HasBoard
{
    use InteractsWithBoardFilters;

    public bool $showBoardFilters = false;

    protected string $view = 'flowforge::filament.pages.board-page';

    public function mount(): void
    {
        // Configure board filters using Filament's table filter system
        $this->boardFilters([
            // Text search filter
            Filter::make('search')
                ->label('Search Tasks')
                ->schema([
                    \Filament\Forms\Components\TextInput::make('query')
                        ->placeholder('Search by title or description...')
                        ->live()
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['query'] ?? null,
                        fn (Builder $query, $search): Builder => $query->where(function (Builder $query) use ($search) {
                            $query->where('title', 'like', "%{$search}%")
                                  ->orWhere('description', 'like', "%{$search}%");
                        })
                    );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['query'] ?? null) {
                        $indicators['search'] = 'Search: ' . $data['query'];
                    }
                    return $indicators;
                }),

            // Status filter
            SelectFilter::make('status')
                ->label('Task Status')
                ->options([
                    'backlog' => 'Backlog',
                    'in_progress' => 'In Progress',
                    'review' => 'Review',
                    'done' => 'Done',
                ])
                ->multiple()
                ->placeholder('All statuses'),

            // Priority filter
            SelectFilter::make('priority')
                ->label('Priority Level')
                ->options([
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                    'urgent' => 'Urgent',
                ])
                ->multiple()
                ->placeholder('All priorities'),

            // Assignee filter
            SelectFilter::make('assignee_id')
                ->label('Assigned To')
                ->relationship('assignee', 'name')
                ->searchable()
                ->preload()
                ->placeholder('Any assignee'),

            // Date range filter
            Filter::make('created_at')
                ->label('Creation Date')
                ->schema([
                    DatePicker::make('created_from')
                        ->label('From')
                        ->placeholder('Start date'),
                    DatePicker::make('created_until')
                        ->label('Until')
                        ->placeholder('End date'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['created_from'] ?? null) {
                        $indicators['created_from'] = 'From: ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                    }
                    if ($data['created_until'] ?? null) {
                        $indicators['created_until'] = 'Until: ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                    }
                    return $indicators;
                }),

            // Soft deletes filter
            TrashedFilter::make()
                ->label('Include Deleted'),

            // Tags filter (custom schema example)
            Filter::make('tags')
                ->label('Tags')
                ->schema([
                    Select::make('tag_ids')
                        ->label('Select Tags')
                        ->multiple()
                        ->options([
                            'bug' => 'Bug',
                            'feature' => 'Feature',
                            'enhancement' => 'Enhancement',
                            'documentation' => 'Documentation',
                        ])
                        ->placeholder('Select tags...')
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['tag_ids'] ?? null,
                        fn (Builder $query, array $tags): Builder => $query->whereJsonContains('tags', $tags)
                    );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($tagIds = $data['tag_ids'] ?? null) {
                        $tagNames = collect($tagIds)->map(fn($id) => ucfirst($id))->join(', ');
                        $indicators['tags'] = 'Tags: ' . $tagNames;
                    }
                    return $indicators;
                }),
        ]);

        // Configure filter behavior
        $this->deferBoardFilters(true) // Require explicit apply action
             ->boardFilterFormMaxWidth('2xl'); // Wider form for better UX
    }

    public function getBoard(): Board
    {
        return Board::make($this)
            ->query(fn () => Task::query())
            ->columnIdentifier('status')
            ->reorderBy('position')
            ->columns([
                Column::make('backlog')
                    ->label('Backlog')
                    ->color('gray'),
                Column::make('in_progress')
                    ->label('In Progress')
                    ->color('blue'),
                Column::make('review')
                    ->label('Review')
                    ->color('yellow'),
                Column::make('done')
                    ->label('Done')
                    ->color('green'),
            ])
            ->cardSchema([
                \Filament\Infolists\Components\TextEntry::make('title')
                    ->weight('semibold'),
                \Filament\Infolists\Components\TextEntry::make('description')
                    ->limit(100),
                \Filament\Infolists\Components\TextEntry::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'blue',
                        'high' => 'orange',
                        'urgent' => 'red',
                    }),
                \Filament\Infolists\Components\TextEntry::make('assignee.name')
                    ->label('Assignee')
                    ->placeholder('Unassigned'),
            ])
            ->searchable(['title', 'description'])
            ->filters($this->getBoardFilters()); // This is the key integration point!
    }

    /**
     * Implementation of the getBaseQueryForColumn method required by InteractsWithBoardFilters.
     */
    protected function getBaseQueryForColumn(string $columnId): Builder
    {
        return Task::query()->where('status', $columnId);
    }

    /**
     * Override resetBoardRecords to refresh the board when filters change.
     */
    protected function resetBoardRecords(): void
    {
        // Force refresh the entire board
        $this->dispatch('$refresh');
    }

    /**
     * Enhanced board records method that applies filters.
     */
    public function getBoardRecords(string $columnId): \Illuminate\Support\Collection
    {
        return $this->getBoardRecordsWithFilters($columnId);
    }

    /**
     * Get the page title.
     */
    public function getTitle(): string
    {
        return 'Filterable Task Board';
    }

    /**
     * Get the page description.
     */
    public function getDescription(): string
    {
        return 'Demonstration of Filament table filters seamlessly integrated into a Flowforge kanban board.';
    }

    public function render()
    {
        return view($this->view, [
            'board' => $this->getBoard(),
        ]);
    }
}