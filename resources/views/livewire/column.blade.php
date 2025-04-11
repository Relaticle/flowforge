@props(['permissions', 'columnId', 'column', 'config'])

<div
    class="kanban-column w-[300px] min-w-[300px] flex-shrink-0 border border-kanban-column-border dark:border-gray-700 shadow-kanban-column dark:shadow-md rounded-xl flex flex-col max-h-full overflow-hidden">
    <!-- Column Header -->
    <div
        class="kanban-column-header flex items-center justify-between py-3 px-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <h3 class="text-sm font-medium text-kanban-column-header dark:text-kanban-column-header">
                {{ $column['label'] }}
            </h3>
            <div
                class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-medium kanban-color-{{ $column['color'] }}">
                {{ $column['total'] ?? (isset($column['items']) ? count($column['items']) : 0) }}
            </div>
        </div>
        @if($permissions['canCreate'])
            <button
                type="button"
                wire:click="openCreateForm('{{ $columnId }}')"
                x-on:click="$dispatch('open-modal', { id: 'create-record-modal' })"
                class="text-gray-400 hover:text-primary-500 dark:text-gray-500 dark:hover:text-primary-400"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                     xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
            </button>
        @endif
    </div>

    <!-- Column Content -->
    <div
        x-sortable
        x-sortable-group="cards"
        data-column-id="{{ $columnId }}"
        @end.stop="$wire.updateRecordsOrderAndColumn($event.to.getAttribute('data-column-id'), $event.to.sortable.toArray())"
        class="p-3 flex-1 overflow-y-auto overflow-x-hidden"
        style="max-height: calc(100vh - 13rem);"
    >
        @if (isset($column['items']) && count($column['items']) > 0)
            @foreach ($column['items'] as $card)
                <x-flowforge::card
                    :card="$card"
                    :config="$config"
                    :columnId="$columnId"
                    wire:key="card-{{ $card['id'] }}"
                />
            @endforeach

            @if(isset($column['total']) && $column['total'] > count($column['items']))
                <div
                    x-intersect.full="
                        if (!isLoadingColumn('{{ $columnId }}')) {
                            beginLoading('{{ $columnId }}');
                            $wire.loadMoreItems('{{ $columnId }}', {{ $config->cardsIncrement ?? 'null' }});
                        }
                    "
                    class="py-3 text-center"
                >
                    <div wire:loading wire:target="loadMoreItems('{{ $columnId }}')"
                         class="text-xs text-primary-600 dark:text-primary-400">
                        {{ __('Loading more cards...') }}
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
                         class="text-xs text-gray-400">
                        {{ count($column['items']) }}
                        / {{ $column['total'] }} {{ $config->pluralCardLabel ?? 'Records' }}
                    </div>
                </div>
            @endif
        @else
            <x-flowforge::empty-column
                :columnId="$columnId"
            />
        @endif
    </div>
</div>
