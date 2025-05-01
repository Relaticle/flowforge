@props(['config', 'columnId', 'record'])

<div
    @class([
        'ff-card kanban-card',
        'ff-card--interactive' => $this->editAction() &&  ($this->editAction)(['record' => $record['id']])->isVisible(),
        'ff-card--non-interactive' => !$this->editAction()
    ])
    x-sortable-handle
    x-sortable-item="{{ $record['id'] }}"
    @if($this->editAction() &&  ($this->editAction)(['record' => $record['id']])->isVisible())
        wire:click="mountAction('edit', {record: '{{ $record['id'] }}'})"
    @endif
>
    <div class="ff-card__content">
        <div class="flex justify-between items-start">
            <h4 class="ff-card__title">{{ $record['title'] }}</h4>

            @if($this->cardActions() && count($this->cardActions()->getActions()) > 0)
                <div class="ff-card__actions" onclick="event.stopPropagation();">
                    <x-filament-actions::group
                        :actions="$this->cardActions()->getActions()"
                        :record="$record['id']"
                        :action="$this->cardActions()"
                        :color="$this->cardActions()->getColor()"
                        :icon="$this->cardActions()->getIcon()"
                        :size="$this->cardActions()->getSize()"
                    />
                </div>
            @endif
        </div>

        @if(!empty($record['description']))
            <p class="ff-card__description">{{ $record['description'] }}</p>
        @endif

        @if(collect($record['attributes'] ?? [])->filter(fn($attribute) => !empty($attribute['value']))->isNotEmpty())
            <div class="ff-card__attributes">
                @foreach($record['attributes'] as $attribute => $data)
                    @if(isset($data) && !empty($data['value']))
                        <x-flowforge::card-badge
                            :label="$data['label']"
                            :value="$data['value']"
                            :color="$data['color'] ?? 'default'"
                            :icon="$data['icon'] ?? null"
                            :type="$data['type'] ?? null"
                            :badge="$data['badge'] ?? null"
                            :rounded="$data['rounded'] ?? 'md'"
                            :size="$data['size'] ?? 'md'"
                        />
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
