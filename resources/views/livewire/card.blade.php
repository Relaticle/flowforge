@props(['config', 'columnId', 'record'])

@php
    $boardPage = app($this->pageClass);
    $recordActions = $boardPage instanceof \Relaticle\Flowforge\BoardPage ? $boardPage->getBoard()->getRecordActions() : [];
    $hasActions = !empty($recordActions);
@endphp

<div
    @class([
        'ff-card kanban-card',
        'ff-card--interactive' => $hasActions,
        'ff-card--non-interactive' => !$hasActions
    ])
    x-sortable-handle
    x-sortable-item="{{ $record['id'] }}"
>
    <div class="ff-card__content">
        <div class="ff-card__header">
            <h4 class="ff-card__title">{{ $record['title'] }}</h4>

            {{-- Render record actions --}}
            @if($hasActions)
                @php
                    $processedRecordActions = $boardPage->getRecordActionsForRecord($record);
                @endphp

                <div class="ff-card__actions">
                    <x-filament-actions::group :actions="$processedRecordActions" />
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
