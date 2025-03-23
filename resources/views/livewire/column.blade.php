@props(['columnId', 'column', 'config'])

<div
    class="flex flex-col h-full min-w-64 w-64 bg-kanban-column-bg rounded-xl shadow-kanban-column p-3 kanban-column"
>
    <!-- Column Header -->
    <div class="flex items-center justify-between p-2 mb-3 font-medium kanban-column-header">
        <div class="flex items-center space-x-2">
            <h3 class="text-kanban-column-header text-sm font-semibold uppercase tracking-wider">{{ $column['name'] }}</h3>
            <div class="flex items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 w-5 h-5 text-xs">
                {{ count($column['items']) }}
            </div>
        </div>
        <button
            type="button"
            class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors"
            x-on:click="openCreateModal('{{ $columnId }}')"
        >
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>

    <!-- Column Content with Scroll Area -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden p-1 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent"
         x-sortable
         x-sortable-group="cards"
         data-column-id="{{ $columnId }}"
         @end.stop="$wire.updateColumnCards($event.to.getAttribute('data-column-id'), $event.to.sortable.toArray())"
    >
        @if(count($column['items']) > 0)
            @foreach($column['items'] as $card)
                <x-flowforge::kanban.card
                    :config="$config"
                    :card="$card"
                    :column-id="$columnId"
                />
            @endforeach
        @else
            <!-- Empty state -->
            <div class="flex flex-col items-center justify-center h-32 kanban-empty-column">
                <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <p class="text-xs text-gray-500 dark:text-gray-400 text-center font-medium">No items yet</p>
                <button
                    x-on:click="openCreateModal('{{ $columnId }}')"
                    class="mt-2 text-xs text-primary-500 hover:text-primary-600 dark:text-primary-400 dark:hover:text-primary-300"
                >
                    Add a card
                </button>
            </div>
        @endif
    </div>
</div>
