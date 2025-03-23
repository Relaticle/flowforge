@props(['columnId', 'column', 'config'])

<div
    class="kanban-column w-[300px] min-w-[300px] flex-shrink-0 border border-kanban-column-border dark:border-gray-700 shadow-kanban-column dark:shadow-md rounded-xl flex flex-col max-h-full overflow-hidden">
    <!-- Column Header -->
    <div
        class="kanban-column-header flex items-center justify-between py-3 px-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <h3 class="text-sm font-medium text-kanban-column-header dark:text-kanban-column-header">
                {{ $column['name'] }}
            </h3>
            <div
                class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $config['statusColors'][$columnId] ?? 'kanban-status-' . str_replace('_', '-', $columnId) }}">
                {{ $column['total'] ?? count($column['items']) }}
            </div>
        </div>
        <button
            type="button"
            wire:click="openCreateForm('{{ $columnId }}')"
            x-on:click="$dispatch('open-modal', { id: 'create-card-modal' })"
            class="text-gray-400 hover:text-primary-500 dark:text-gray-500 dark:hover:text-primary-400"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
        </button>
    </div>

    <!-- Column Content -->
    <div
        x-sortable
        x-sortable-group="cards"
        data-column-id="{{ $columnId }}"
        @end.stop="$wire.updateColumnCards($event.to.getAttribute('data-column-id'), $event.to.sortable.toArray())"
        class="p-3 flex-1 overflow-y-auto overflow-x-hidden"
        style="max-height: calc(100vh - 13rem);"
    >
        @if (count($column['items']) > 0)
            @foreach ($column['items'] as $card)
                <x-flowforge::card
                    :card="$card"
                    :config="$config"
                    :columnId="$columnId"
                    wire:key="card-{{ $card['id'] }}"
                />
            @endforeach

            @if($column['total'] > count($column['items']))
                <button
                    wire:click="loadMoreItems('{{ $columnId }}')"
                    class="w-full py-2 text-xs text-center text-primary-600 dark:text-primary-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150 rounded"
                >
                    {{ __('Load more') }} ({{ count($column['items']) }} / {{ $column['total'] }})
                </button>
            @endif
        @else
           <x-flowforge::empty-column
                :columnId="$columnId"
            />
        @endif
    </div>
</div>
