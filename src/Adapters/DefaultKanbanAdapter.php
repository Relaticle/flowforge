<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Adapters;

use DB;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Wireable;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;

class DefaultKanbanAdapter implements IKanbanAdapter, Wireable
{
    /**
     * The model class for the adapter.
     *
     * @var EloquentBuilder|Relation
     */
    protected EloquentBuilder|Relation $subject;

    /**
     * The status field for the model.
     *
     * @var string
     */
    protected string $statusField;

    /**
     * The status values for the model.
     *
     * @var array<string, string>
     */
    protected array $statusValues;

    /**
     * The title attribute for the model.
     *
     * @var string
     */
    protected string $titleAttribute;

    /**
     * The description attribute for the model.
     *
     * @var string|null
     */
    protected ?string $descriptionAttribute;

    /**
     * The card attributes for the model.
     *
     * @var array<string>
     */
    protected array $cardAttributes = [];

    /**
     * The status colors for the model.
     *
     * @var array<string, string>|null
     */
    protected ?array $statusColors = null;

    /**
     * The order field for the model.
     *
     * @var string|null
     */
    protected ?string $orderField = null;


    /**
     * The create form callable for the model.
     *
     * @var callable|null
     */
    protected mixed $createFormCallable = null;

    /**
     * The singular label for the model.
     *
     * @var string
     */
    protected string $recordLabel;

    /**
     * The plural label for the model.
     *
     * @var string
     */
    protected string $pluralRecordLabel;

    /**
     * Create a new adapter instance.
     *
     * @param EloquentBuilder|Relation $subject The model class
     * @param string $statusField The status field
     * @param array<string, string> $statusValues The status values
     * @param string $titleAttribute The title attribute
     * @param string|null $descriptionAttribute The description attribute
     * @param array<string> $cardAttributes The card attributes
     * @param array<string, string>|null $statusColors The status colors
     * @param string|null $orderField The order field
     * @param callable|null $createFormCallable
     * @param string|null $recordLabel The singular label for the model
     * @param string|null $pluralRecordLabel The plural label for the model
     */
    public function __construct(
        EloquentBuilder|Relation  $subject,
        string  $statusField,
        array   $statusValues,
        string  $titleAttribute,
        ?string $descriptionAttribute = null,
        array   $cardAttributes = [],
        ?array  $statusColors = null,
        ?string $orderField = null,
        ?callable $createFormCallable = null,
        ?string $recordLabel = null,
        ?string $pluralRecordLabel = null
    )
    {
        $this->subject = $subject;
        $this->statusField = $statusField;
        $this->statusValues = $statusValues;
        $this->titleAttribute = $titleAttribute;
        $this->descriptionAttribute = $descriptionAttribute;
        $this->cardAttributes = $cardAttributes;
        $this->statusColors = $statusColors;
        $this->orderField = $orderField;

        $this->createFormCallable = $createFormCallable;

        // Set model labels with defaults
        $this->recordLabel = $recordLabel ?? Str::singular(class_basename($subject->getModel()));
        $this->pluralRecordLabel = $pluralRecordLabel ?? Str::singular(class_basename($subject->getModel()));
    }

    /**
     * Find a model by its ID.
     *
     * @param mixed $id The model ID
     * @return Model|null
     */
    public function getModelById($id): ?Model
    {
        return $this->subject->find($id);
    }

    /**
     * Get the status field for the model.
     *
     * @return string
     */
    public function getStatusField(): string
    {
        return $this->statusField;
    }

    /**
     * Get the status values for the model.
     *
     * @return array<string, string>
     */
    public function getStatusValues(): array
    {
        return $this->statusValues;
    }

    /**
     * Get the title attribute for the model.
     *
     * @return string
     */
    public function getTitleAttribute(): string
    {
        return $this->titleAttribute;
    }

    /**
     * Get the description attribute for the model.
     *
     * @return string|null
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->descriptionAttribute;
    }

    /**
     * Get the card attributes for the model.
     *
     * @return array<string>
     */
    public function getCardAttributes(): array
    {
        return $this->cardAttributes;
    }

    /**
     * Get the order field for the model.
     *
     * @return string|null
     */
    public function getOrderField(): ?string
    {
        return $this->orderField;
    }

    /**
     * Get the singular label for the model.
     *
     * @return string
     */
    public function getRecordLabel(): string
    {
        return $this->recordLabel;
    }

    /**
     * Get the plural label for the model.
     *
     * @return string
     */
    public function getPluralRecordLabel(): string
    {
        return $this->pluralRecordLabel;
    }

    /**
     * Get the color for each status.
     * If not implemented or null is returned for a status, DEFAULT will be used.
     *
     * @return array<string, string>|null
     */
    public function getStatusColors(): ?array
    {
        return $this->statusColors;
    }

    /**
     * Get the items for all statuses.
     *
     * @return Collection<int, Model>
     */
    public function getItems(): Collection
    {
        return $this->subject->all();
    }

    /**
     * Get the items for a specific status with pagination.
     *
     * @param string|int $status The status value
     * @param int $limit The number of items to return
     * @return Collection<int, Model>
     */
    public function getItemsForStatus(string|int $status, int $limit = 10): Collection
    {
        $query = $this->subject->where($this->getStatusField(), $status);

        // Add ordering if order field is set
        if ($this->getOrderField()) {
            $query->orderBy($this->getOrderField());
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get the total count of items for a specific status.
     *
     * @param string|int $status The status value
     * @return int
     */
    public function getTotalItemsCount(string|int $status): int
    {
        return $this->subject->where($this->getStatusField(), $status)->count();
    }

    /**
     * Update the status of a model.
     *
     * @param Model $model The model to update
     * @param string $status The new status value
     * @return bool
     */
    public function updateStatus(Model $model, string $status): bool
    {
        $model->{$this->getStatusField()} = $status;
        return $model->save();
    }

    /**
     * Update the order of a model.
     *
     * @param string|int $columnId
     * @param array $cards
     * @return bool
     * @throws \Throwable
     */
    public function updateColumnCards(string|int $columnId, array $cards): bool
    {
        if (!$this->getOrderField()) {
            return false;
        }

        $model = app($this->subject->getModel());

        // Validate column ID exists in status values
        if (!array_key_exists((string)$columnId, $this->getStatusValues())) {
            return false;
        }

        return DB::transaction(function () use ($model, $columnId, $cards) {
            foreach ($cards as $index => $id) {
                $model->newQuery()
                    ->where($model->getQualifiedKeyName(), $id)
                    ->update([
                        $this->getStatusField() => $columnId,
                        $this->getOrderField() => $index,
                    ]);
            }

            return true;
        });
    }

    /**
     * Get the form class for creating cards.
     *
     * @param Form $form
     * @return Form
     */
    public function getEditForm(Form $form): Form
    {
        return $form
            ->statePath('editFormData')
            ->schema([
                Select::make($this->getStatusField())
                    ->label(__('Status'))
                    ->options($this->getStatusValues())
                    ->required(),

                TextInput::make('title')
                    ->label(__('Title'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('Enter :recordLabel title', ['recordLabel' => strtolower($this->config['recordLabel'] ?? 'card')]))
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label(__('Description'))
                    ->placeholder(__('Enter :recordLabel description', ['recordLabel' => strtolower($this->config['recordLabel'] ?? 'card')]))
                    ->columnSpanFull(),

                $this->getCardAttributesFields(),
            ]);
    }

    /**
     * Get the form class for creating cards.
     *
     * @param Form $form
     * @param mixed $activeColumn
     * @return Form
     */
    public function getCreateForm(Form $form, mixed $activeColumn): Form {
        if($this->createFormCallable) {
            return call_user_func($this->createFormCallable, $form, $activeColumn);
        }

        return $form
            ->statePath('createFormData')
            ->schema([
                Hidden::make($this->getStatusField())
                    ->default(fn () => $activeColumn),

                TextInput::make('title')
                    ->label(__('Title'))
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('Enter :recordLabel title', ['recordLabel' => strtolower($this->config['recordLabel'] ?? 'card')]))
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label(__('Description'))
                    ->placeholder(__('Enter :recordLabel description', ['recordLabel' => strtolower($this->config['recordLabel'] ?? 'card')]))
                    ->columnSpanFull(),

                $this->getCardAttributesFields(),
            ]);
    }

    /**
     * Generate form fields for card attributes.
     *
     * @return Section|null
     */
    protected function getCardAttributesFields(): ?Grid
    {
        $cardAttributes = $this->getCardAttributes();

        if (empty($cardAttributes)) {
            return null;
        }

        $fields = [];

        foreach ($cardAttributes as $attribute => $label) {
            // Determine field type based on attribute name
            if (str_contains($attribute, 'date')) {
                $fields[] = DatePicker::make($attribute)
                    ->label($label);
            } elseif (str_contains($attribute, 'priority')) {
                $fields[] = Select::make($attribute)
                    ->label($label)
                    ->options([
                        'Low' => __('Low'),
                        'Medium' => __('Medium'),
                        'High' => __('High'),
                    ]);
            } else {
                $fields[] = TextInput::make($attribute)
                    ->label($label)
                    ->maxLength(255);
            }
        }

        if (empty($fields)) {
            return null;
        }

        return Grid::make(__('Additional Details'))
            ->schema($fields)
            ->columns(2);
    }

    /**
     * Create a new card with the given attributes.
     *
     * @param array<string, mixed> $attributes The card attributes
     * @return Model|null
     */
    public function createCard(array $attributes): ?Model
    {
        $modelClass = $this->subject->getModel();
        $card = new $modelClass();

        // Set status if provided, otherwise use the first status as default
        $status = $attributes[$this->getStatusField()] ?? array_key_first($this->getStatusValues());
        $card->{$this->getStatusField()} = $status;

        // Set order if the field exists
        if ($this->getOrderField()) {
            // Set the highest order by default (add to the end of the column)
            $maxOrder = $modelClass::where($this->getStatusField(), $status)
                ->max($this->getOrderField()) ?? 0;

            $card->{$this->getOrderField()} = $maxOrder + 1;
        }

        // Set title
        $this->setTitle($attributes, $card);

        return $card->save() ? $card : null;
    }

    /**
     * Update an existing card with the given attributes.
     *
     * @param Model $card The card to update
     * @param array<string, mixed> $attributes The card attributes to update
     * @return bool
     */
    public function updateCard(Model $card, array $attributes): bool
    {
        // Update status if provided
        if (isset($attributes[$this->getStatusField()])) {
            $card->{$this->getStatusField()} = $attributes[$this->getStatusField()];
        }

        // Update title if provided
        $this->setTitle($attributes, $card);

        return $card->save();
    }

    /**
     * Delete an existing card.
     *
     * @param Model $card The card to delete
     * @return bool
     */
    public function deleteCard(Model $card): bool
    {
        return $card->delete();
    }

    /**
     * Convert the adapter to a Livewire-compatible array.
     *
     * @return array
     */
    public function toLivewire(): array
    {
        return [
            'subject' => $this->subject->toArray(),
            'statusField' => $this->getStatusField(),
            'statusValues' => $this->getStatusValues(),
            'titleAttribute' => $this->getTitleAttribute(),
            'descriptionAttribute' => $this->getDescriptionAttribute(),
            'cardAttributes' => $this->getCardAttributes(),
            'statusColors' => $this->getStatusColors(),
            'orderField' => $this->getOrderField(),
            'createFormCallable' => $this->createFormCallable,
            'recordLabel' => $this->getRecordLabel(),
            'pluralRecordLabel' => $this->getPluralRecordLabel(),
        ];
    }

    /**
     * Create a new adapter instance from a Livewire-compatible array.
     *
     * @param array<string, mixed> $value The Livewire-compatible array
     * @return static
     */
    public static function fromLivewire($value): static
    {
        return new static(
            $value['subject'],
            $value['statusField'],
            $value['statusValues'],
            $value['titleAttribute'],
            $value['descriptionAttribute'] ?? null,
            $value['cardAttributes'] ?? [],
            $value['statusColors'] ?? null,
            $value['orderField'] ?? null,
             $value['createFormCallable'] ?? null,
            $value['recordLabel'] ?? null,
            $value['pluralRecordLabel'] ?? null
        );
    }

    /**
     * @param array $attributes
     * @param mixed $card
     * @return void
     */
    public function setTitle(array $attributes, mixed $card): void
    {
        if (isset($attributes[$this->getTitleAttribute()])) {
            $card->{$this->getTitleAttribute()} = $attributes[$this->getTitleAttribute()];
        }

        // Set description if the attribute exists
        if ($this->getDescriptionAttribute() && isset($attributes[$this->getDescriptionAttribute()])) {
            $card->{$this->getDescriptionAttribute()} = $attributes[$this->getDescriptionAttribute()];
        }

        // Set additional card attributes
        foreach ($this->getCardAttributes() as $attribute => $label) {
            if (isset($attributes[$attribute])) {
                $card->{$attribute} = $attributes[$attribute];
            }
        }
    }
}
