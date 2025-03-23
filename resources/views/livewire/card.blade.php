@props(['config', 'columnId', 'card'])

<div
    x-sortable-handle
    x-sortable-item="{{ $card['id'] }}"
    x-on:click.stop="openEditModal({{ json_encode($card) }}, '{{ $columnId }}')"
    class="group relative overflow-hidden bg-kanban-card-bg hover:bg-kanban-card-hover dark:bg-kanban-card-bg dark:hover:bg-kanban-card-hover rounded-lg border border-kanban-card-border dark:border-gray-700 shadow-kanban-card hover:shadow-kanban-card-hover mb-3 p-4 transition-kanban kanban-card animate-kanban-card-add {{ isset($card['priority']) ? 'priority-' . strtolower($card['priority']) : '' }}"
>
    <!-- Priority Indicator (5px vertical bar) -->
    @if(isset($card['priority']))
        <div class="absolute left-0 top-0 bottom-0 w-1.5 @if($card['priority'] == 'high') bg-kanban-priority-high @elseif($card['priority'] == 'medium') bg-kanban-priority-medium @else bg-kanban-priority-low @endif"></div>
    @endif

    <!-- Card Header -->
    <div class="flex items-start justify-between mb-2">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white line-clamp-2 pr-6">{{ $card['title'] }}</h4>
        <div class="flex items-center kanban-drag-handle ml-1 mt-0.5">
            <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-400 cursor-grab" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
            </svg>
        </div>
    </div>

    <!-- Card Description -->
    @if(isset($card['description']) && $card['description'])
        <div class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2 mb-3 pl-0.5">
            {{ $card['description'] }}
        </div>
    @endif

    <!-- Card Attributes -->
    <div class="flex flex-wrap gap-1.5 mt-2.5">
        @foreach($config['cardAttributes'] as $attribute => $label)
            @if(isset($card[$attribute]) && $card[$attribute])
                <div class="kanban-card-badge bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 flex items-center">
                    @if($attribute == 'due_date')
                        <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    @elseif($attribute == 'priority')
                        <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path>
                        </svg>
                    @endif
                    <span class="whitespace-nowrap overflow-hidden text-ellipsis">{{ $card[$attribute] }}</span>
                </div>
            @endif
        @endforeach

        <!-- Optional Indicators -->
        @if(isset($card['assignee']))
            <div class="kanban-card-badge bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400 flex items-center">
                <svg class="w-3 h-3 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span class="whitespace-nowrap overflow-hidden text-ellipsis">{{ $card['assignee'] }}</span>
            </div>
        @endif
    </div>
</div>
