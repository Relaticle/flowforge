# Customization

> Customize your Kanban boards with rich content, actions, and filtering.

## Rich Card Content

Use Filament's Schema components to create rich card layouts:

```php
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

public function board(Board $board): Board
{
    return $board
        ->cardSchema(fn (Schema $schema) => $schema->components([
            TextEntry::make('priority')->badge()->color(fn ($state) => match($state) {
                'high' => 'danger',
                'medium' => 'warning',
                'low' => 'success',
                default => 'gray'
            }),
            TextEntry::make('due_date')->date()->icon('heroicon-o-calendar'),
            TextEntry::make('assignee.name')->icon('heroicon-o-user'),
        ]));
}
```

## CardFlex Layout

Use `CardFlex` to arrange multiple elements in a flexible row with intelligent wrapping:

```php
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Relaticle\Flowforge\Components\CardFlex;

->cardSchema(fn (Schema $schema) => $schema->components([
    TextEntry::make('title')->weight('bold'),
    CardFlex::make([
        TextEntry::make('priority')->badge(),
        TextEntry::make('due_date')->icon('heroicon-o-calendar'),
        ImageEntry::make('assignee.avatar_url')->circular()->size(24),
    ])
        ->wrap()              // Enable wrapping on small screens
        ->justify('between')  // Horizontal: 'start', 'end', 'between', 'center'
        ->align('center'),    // Vertical: 'start', 'end', 'center'
]))
```

### CardFlex Options

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
      Default
    </th>
  </tr>
</thead>

<tbody>
  <tr>
    <td>
      <code>
        wrap(bool $wrap = true)
      </code>
    </td>
    
    <td>
      Enable/disable wrapping
    </td>
    
    <td>
      <code>
        true
      </code>
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        justify(string $justify)
      </code>
    </td>
    
    <td>
      Horizontal alignment
    </td>
    
    <td>
      <code>
        'start'
      </code>
    </td>
  </tr>
  
  <tr>
    <td>
      <code>
        align(string $align)
      </code>
    </td>
    
    <td>
      Vertical alignment
    </td>
    
    <td>
      <code>
        'center'
      </code>
    </td>
  </tr>
</tbody>
</table>

### Real-World Example

```php
->cardSchema(fn (Schema $schema) => $schema->components([
    TextEntry::make('title')
        ->weight('bold')
        ->size('lg'),
    TextEntry::make('description')
        ->limit(100)
        ->color('gray'),
    CardFlex::make([
        TextEntry::make('priority')
            ->badge()
            ->icon('heroicon-o-flag'),
        TextEntry::make('due_date')
            ->badge()
            ->date()
            ->icon('heroicon-o-calendar'),
        TextEntry::make('assignedTo.name')
            ->badge()
            ->icon('heroicon-o-user'),
    ])->wrap()->justify('start'),
]))
```

## Column Actions

Add actions to column headers for creating new cards or bulk operations:

```php
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

public function board(Board $board): Board
{
    return $board
        ->columnActions([
            CreateAction::make()
                ->label('Add Task')
                ->model(Task::class)
                ->form([
                    TextInput::make('title')->required(),
                    Select::make('priority')
                        ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                        ->default('medium'),
                ])
                ->mutateFormDataUsing(function (array $data, array $arguments): array {
                    if (isset($arguments['column'])) {
                        $data['status'] = $arguments['column'];
                        $data['position'] = $this->getBoardPositionInColumn($arguments['column']);
                    }
                    return $data;
                }),
        ]);
}
```

## Card Actions

Add actions to individual cards for editing, deleting, or custom operations:

```php
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

public function board(Board $board): Board
{
    return $board
        ->cardActions([
            EditAction::make()->model(Task::class),
            DeleteAction::make()->model(Task::class),
        ])
        ->cardAction('edit'); // Makes cards clickable
}
```

## Search and Filtering

Enable powerful search and filtering capabilities:

```php
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

public function board(Board $board): Board
{
    return $board
        ->searchable(['title', 'description', 'assignee.name'])
        ->filters([
            SelectFilter::make('priority')
                ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                ->multiple(),
            SelectFilter::make('assigned_to')
                ->relationship('assignee', 'name')
                ->searchable()
                ->preload(),
            Filter::make('overdue')
                ->label('Overdue')
                ->query(fn (Builder $query) => $query->where('due_date', '<', now()))
                ->toggle(),
        ]);
}
```

## Column Configuration

Customize column appearance and behavior:

```php
use Filament\Support\Colors\Color;

Column::make('todo')
    ->label('To Do')
    ->color('gray')        // Supports multiple color formats
    ->icon('heroicon-o-queue-list')
```

### Available Colors

Flowforge supports four flexible ways to set column colors:

#### 1. Semantic Colors (Filament registered)

Use your application's theme colors:

- `primary` - Your app's primary color
- `secondary` - Secondary theme color
- `success` - Green success color
- `warning` - Yellow/amber warning color
- `danger` - Red danger/error color
- `info` - Blue informational color
- `gray` - Neutral gray color

#### 2. Filament Color Constants

Use Filament's Color class constants directly:

```php
use Filament\Support\Colors\Color;

Column::make('todo')->color(Color::Gray)
Column::make('in_progress')->color(Color::Blue)
Column::make('done')->color(Color::Green)
```

Available constants: `Color::Slate`, `Color::Gray`, `Color::Zinc`, `Color::Neutral`, `Color::Stone`, `Color::Red`, `Color::Orange`, `Color::Amber`, `Color::Yellow`, `Color::Lime`, `Color::Green`, `Color::Emerald`, `Color::Teal`, `Color::Cyan`, `Color::Sky`, `Color::Blue`, `Color::Indigo`, `Color::Violet`, `Color::Purple`, `Color::Fuchsia`, `Color::Pink`, `Color::Rose`

#### 3. Tailwind CSS Color Names

Use color names as strings (case-insensitive):

```php
Column::make('todo')->color('gray')
Column::make('in_progress')->color('blue')
Column::make('done')->color('green')
```

#### 4. Custom Hex Colors

Any valid hex color code:

```php
Column::make('urgent')->color('#ff0000')
Column::make('normal')->color('#3b82f6')
Column::make('completed')->color('#22c55e')
```

### Complete Example

```php
use Filament\Support\Colors\Color;

->columns([
    Column::make('todo')
        ->color('gray'),                    // Tailwind color name
    Column::make('in_progress')
        ->color(Color::Blue),                // Color constant
    Column::make('review')
        ->color('primary'),                  // Semantic color
    Column::make('done')
        ->color('#22c55e'),                  // Custom hex
])
```
