<?php

namespace Relaticle\Flowforge\Adapters;

use App\Models\Task;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

class ExampleKanbanAdapter implements IKanbanAdapter
{
    /**
     * Get the model class.
     */
    public function getModel(): string
    {
        return Task::class;
    }

    /**
     * Find a model by its ID.
     *
     * @param  mixed  $id  The model ID
     */
    public function getModelById($id): ?Model
    {
        return Task::find($id);
    }

    /**
     * Get the status field name for the model.
     */
    public function getStatusField(): string
    {
        return 'status';
    }

    /**
     * Get all available status values for the model.
     *
     * @return array<string, string>
     */
    public function getStatusValues(): array
    {
        return [
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'in_review' => 'In Review',
            'done' => 'Done',
        ];
    }

    /**
     * Get the color for each status.
     * If not implemented or null is returned for a status, DEFAULT will be used.
     *
     * @return array<string, string>|null
     */
    public function getStatusColors(): ?array
    {
        return [
            'todo' => 'blue',
            'in_progress' => 'yellow',
            'in_review' => 'purple',
            'done' => 'green',
        ];
    }

    // Other required interface methods would go here

    /* Required interface methods implementation omitted for brevity */

    public function getItems(): Collection
    {
        return new Collection;
    }

    public function getItemsForStatus(string $status, int $limit = 10): Collection
    {
        return new Collection;
    }

    public function getTotalItemsCount(string $status): int
    {
        return 0;
    }

    public function updateStatus(Model $model, string $status): bool
    {
        return true;
    }

    public function getCardAttributes(): array
    {
        return [];
    }

    public function getTitleAttribute(): string
    {
        return 'title';
    }

    public function getDescriptionAttribute(): ?string
    {
        return 'description';
    }

    public function getCreateForm(Form $form, mixed $activeColumn): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required(),
                MarkdownEditor::make('description'),
                Select::make('status')
                    ->options($this->getStatusValues())
                    ->default($activeColumn)
                    ->required(),
            ]);
    }

    public function getEditForm(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required(),
                MarkdownEditor::make('description'),
                Select::make('status')
                    ->options($this->getStatusValues())
                    ->required(),
            ]);
    }

    public function createCard(array $attributes): ?Model
    {
        return Task::create($attributes);
    }

    public function updateCard(Model $card, array $attributes): bool
    {
        return $card->update($attributes);
    }

    public function deleteCard(Model $card): bool
    {
        return $card->delete();
    }

    public function getOrderField(): ?string
    {
        return 'order';
    }

    public function updateColumnCards(string | int $columnId, array $cards): bool
    {
        return true;
    }
}
