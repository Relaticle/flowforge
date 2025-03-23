@props(['columnId', 'column', 'config'])

<div class="kanban-column w-[300px] min-w-[300px] flex-shrink-0 bg-kanban-column-bg dark:bg-kanban-column-bg border border-kanban-column-border dark:border-gray-700 shadow-kanban-column dark:shadow-md rounded-xl flex flex-col max-h-full overflow-hidden">
    <!-- Column Header -->
    <div class="kanban-column-header flex items-center justify-between py-3 px-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center">
            <h3 class="text-sm font-medium text-kanban-column-header dark:text-kanban-column-header">
                {{ $column['name'] }}
            </h3>
            <div class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-medium kanban-status-{{ str_replace('_', '-', $columnId) }}">
                {{ count($column['items']) }}
            </div>
        </div>
        <button
            type="button"
            @click="openCreateModal('{{ $columnId }}')"
            class="text-gray-400 hover:text-primary-500 dark:text-gray-500 dark:hover:text-primary-400"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
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
                <x-flowforge::kanban.card
                    :config="$config"
                    :columnId="$columnId"
                    :card="$card"
                />
            @endforeach
        @else
            <div class="kanban-empty-column h-24 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 text-sm">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>No items</span>
            </div>
        @endif
    </div>
</div>
