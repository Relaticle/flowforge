# API Reference

> Complete reference for Flowforge configuration methods.

## Board Configuration

<table>
<thead>
  <tr>
    <th>
      Method
    </th>
    
    <th>
      Description
    </th>
    
    <th>
      Required
    </th>
  </tr>
</thead>

<tbody>
  <tr>
    <td>
      <code>
        query(Builder)
      </code>
    </td>
    
    <td>
      Set the Eloquent query as data source
    </td>
    
    <td>
      Yes
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        columnIdentifier(string)
      </code>
    </td>
    
    <td>
      Field name for column status
    </td>
    
    <td>
      Yes
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        positionIdentifier(string)
      </code>
    </td>
    
    <td>
      Field name for drag-drop ordering
    </td>
    
    <td>
      Yes
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        columns(array)
      </code>
    </td>
    
    <td>
      Define board columns
    </td>
    
    <td>
      Yes
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        recordTitleAttribute(string)
      </code>
    </td>
    
    <td>
      Field name for card titles
    </td>
    
    <td>
      
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        cardSchema(Closure)
      </code>
    </td>
    
    <td>
      Rich card content using Schema
    </td>
    
    <td>
      
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        cardActions(array)
      </code>
    </td>
    
    <td>
      Actions available on each card
    </td>
    
    <td>
      
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        columnActions(array)
      </code>
    </td>
    
    <td>
      Actions available on column headers
    </td>
    
    <td>
      
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        searchable(array)
      </code>
    </td>
    
    <td>
      Enable search on specified fields
    </td>
    
    <td>
      
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        filters(array)
      </code>
    </td>
    
    <td>
      Add filtering capabilities
    </td>
    
    <td>
      
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        cardAction(string)
      </code>
    </td>
    
    <td>
      Make cards clickable with action
    </td>
    
    <td>
      
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        cardsPerColumn(int)
      </code>
    </td>
    
    <td>
      Cards to load per column (pagination)
    </td>
    
    <td>
      
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        filtersFormWidth(Width)
      </code>
    </td>
    
    <td>
      Filter panel width
    </td>
    
    <td>
      
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        filtersFormColumns(int)
      </code>
    </td>
    
    <td>
      Columns in filter form
    </td>
    
    <td>
      
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        filtersLayout(FiltersLayout)
      </code>
    </td>
    
    <td>
      Filter display layout (Dropdown, AboveContent, etc.)
    </td>
    
    <td>
      
    </td>
  </tr>
</tbody>
</table>

## Board Methods

### Essential Configuration

```php
public function board(Board $board): Board
{
    return $board
        ->query(Task::query())                    // Required: Data source
        ->columnIdentifier('status')              // Required: Column field
        ->positionIdentifier('position')          // Required: Position field
        ->columns([                               // Required: Column definitions
            Column::make('todo')->label('To Do')->color('gray'),
            Column::make('done')->label('Done')->color('green'),
        ]);
}
```

### Content Configuration

```php
->recordTitleAttribute('title')                   // Card title field
->cardSchema(fn(Schema $schema) => $schema        // Rich card content
    ->components([
        TextEntry::make('description'),
        TextEntry::make('due_date')->date(),
    ])
)
```

### Actions Configuration

```php
->cardActions([                                   // Card-level actions
    EditAction::make()->model(Task::class),
    DeleteAction::make()->model(Task::class),
])
->columnActions([                                 // Column-level actions
    CreateAction::make()->model(Task::class),
])
->cardAction('edit')                              // Make cards clickable
```

### Search & Filtering

```php
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Enums\Width;

->searchable(['title', 'description'])            // Enable search
->filters([                                       // Add filters
    SelectFilter::make('priority'),
    Filter::make('overdue')->query(fn($q) =>
        $q->where('due_date', '<', now())
    ),
])
->filtersLayout(FiltersLayout::AboveContent)      // Display filters above board
->filtersFormWidth(Width::Large)                  // Filter panel width
->filtersFormColumns(3)                           // Columns in filter form
```

## Column Configuration

```php
Column::make('identifier')
    ->label('Display Name')                       // Column header text
    ->color('blue')                              // Column color theme
    ->icon('heroicon-o-flag')                    // Column header icon
```

### Available Colors

- `gray` - Neutral/default
- `blue` - Primary/in-progress
- `green` - Success/completed
- `red` - Error/urgent
- `amber` - Warning/review
- `purple` - Custom status
- `pink` - Custom status

## Livewire Methods

These methods are available in your board components for programmatic control:

### Card Management

```php
// Move card between columns
$this->moveCard(string $cardId, string $targetColumnId, ?string $afterCardId = null, ?string $beforeCardId = null)
```

### Pagination Control

```php
// Load more cards for pagination
$this->loadMoreItems(string $columnId, ?int $count = null)

// Load all cards to enable reordering
$this->loadAllItems(string $columnId)

// Check if all cards are loaded
$this->isColumnFullyLoaded(string $columnId): bool

// Get position for new card in column
$this->getBoardPositionInColumn(string $columnId): string
```

## Performance Features

- **Intelligent Pagination**: Efficiently handles 100+ cards per column
- **Infinite Scroll**: Smooth loading with 80% scroll threshold
- **Optimistic UI**: Immediate feedback with rollback on errors
- **Fractional Ranking**: Prevents database locks during reordering
- **Query Optimization**: Cursor-based pagination with eager loading

## Livewire Events

Flowforge dispatches these events for frontend integration and custom logic:

<table>
<thead>
  <tr>
    <th>
      Event
    </th>
    
    <th>
      Payload
    </th>
    
    <th>
      When Fired
    </th>
  </tr>
</thead>

<tbody>
  <tr>
    <td>
      <code>
        kanban-card-moved
      </code>
    </td>
    
    <td>
      <code>
        cardId
      </code>
      
      , <code>
        columnId
      </code>
      
      , <code>
        position
      </code>
    </td>
    
    <td>
      After a card is moved
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        kanban-items-loaded
      </code>
    </td>
    
    <td>
      <code>
        columnId
      </code>
      
      , <code>
        loadedCount
      </code>
      
      , <code>
        totalCount
      </code>
      
      , <code>
        isFullyLoaded
      </code>
    </td>
    
    <td>
      After pagination loads more cards
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        kanban-all-items-loaded
      </code>
    </td>
    
    <td>
      <code>
        columnId
      </code>
      
      , <code>
        totalCount
      </code>
    </td>
    
    <td>
      After "Load All" completes
    </td>
  </tr>
</tbody>
</table>

### Listening to Events

```php
use Livewire\Attributes\On;

#[On('kanban-card-moved')]
public function onCardMoved(string $cardId, string $columnId, string $position): void
{
    // Custom logic when a card moves (e.g., send notification, update analytics)
}

#[On('kanban-items-loaded')]
public function onItemsLoaded(string $columnId, int $loadedCount, int $totalCount, bool $isFullyLoaded): void
{
    // Track loading progress
}
```

### JavaScript Listeners

```javascript
document.addEventListener('livewire:init', () => {
    Livewire.on('kanban-card-moved', ({ cardId, columnId }) => {
        console.log(`Card ${cardId} moved to ${columnId}`);
    });
});
```
