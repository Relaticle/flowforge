@props(['columnId', 'column', 'config'])

<div
    class="ff-column kanban-column">
    <!-- Column Header -->
    <div class="ff-column__header">
        <div class="ff-column__title-container">
            <h3 class="ff-column__title">
                {{ $column['label'] }}
            </h3>
            <div class="ff-column__count kanban-color-{{ $column['color'] ?? 'default' }}">
                {{ $column['total'] ?? (isset($column['items']) ? count($column['items']) : 0) }}
            </div>
        </div>

        {{-- Render column actions (only if not readonly) --}}
        @if(!$this->getBoard()->isReadonly())
            @php
                $processedActions = $this->getColumnActionsForColumn($columnId);
            @endphp
            
            @if(count($processedActions) > 0)
                <div class="ff-column__actions">
                    @if(count($processedActions) === 1)
                        {{ $processedActions[0] }}
                    @else
                        <x-filament-actions::group :actions="$processedActions" />
                    @endif
                </div>
            @endif
        @endif
    </div>

    <!-- Column Content -->
    <div
        @if(!$this->getBoard()->isReadonly())
            x-sortable
            x-sortable-group="cards"
            data-column-id="{{ $columnId }}"
            @end.stop="$wire.updateRecordsOrderAndColumn($event.to.getAttribute('data-column-id'), $event.to.sortable.toArray())"
        @endif
        class="ff-column__content"
        style="max-height: calc(100vh - 13rem);"
    >
        @if (isset($column['items']) && count($column['items']) > 0)
            @foreach ($column['items'] as $record)
                <x-flowforge::card
                    :record="$record"
                    :config="$config"
                    :columnId="$columnId"
                    wire:key="card-{{ $record['id'] }}"
                />
            @endforeach

            @if(isset($column['total']) && $column['total'] > count($column['items']))
                <div
                    x-intersect.full="
                        if (!isLoadingColumn('{{ $columnId }}')) {
                            beginLoading('{{ $columnId }}');
                            $wire.loadMoreItems('{{ $columnId }}', {{ $this->cardsIncrement ?? 'null' }});
                        }
                    "
                    class="ff-column__loader"
                >
                    <div wire:loading wire:target="loadMoreItems('{{ $columnId }}')"
                         class="ff-column__loading-text">
                        {{ __('flowforge::flowforge.loading_more_cards') }}
                        <div class="mt-1 flex justify-center">
                            <svg class="animate-spin h-4 w-4 text-primary-600 dark:text-primary-400"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                    <div wire:loading.remove wire:target="loadMoreItems('{{ $columnId }}')"
                         class="ff-column__count-text">
                        {{ count($column['items']) }}
                        / {{ $column['total'] }} {{ $config->getPluralCardLabel() }}
                    </div>
                </div>
            @endif
        @else
            <x-flowforge::empty-column
                :columnId="$columnId"
                :pluralCardLabel="$config->getPluralCardLabel()"
            />
        @endif
    </div>
</div>
