@props(['config', 'columnId', 'card'])

<div
    x-sortable-handle
    x-sortable-item="{{ $card['id'] }}"
    x-on:click.stop="openEditModal({{ json_encode($card) }}, '{{ $columnId }}')"
    class="group bg-kanban-card-bg hover:bg-kanban-card-hover dark:bg-kanban-card-bg dark:hover:bg-kanban-card-hover rounded-lg shadow-kanban-card hover:shadow-kanban-card-hover mb-3 p-3 transition-kanban kanban-card animate-kanban-card-add {{ isset($card['priority']) ? 'priority-' . strtolower($card['priority']) : '' }}"
>
    <!-- Card Header -->
    <div class="flex items-center justify-between mb-2">
        <h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">{{ $card['title'] }}</h4>
        <div class="flex kanban-drag-handle">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
            </svg>
        </div>
    </div>

    <!-- Card Description -->
    @if(isset($card['description']) && $card['description'])
        <div class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-2">
            {{ $card['description'] }}
        </div>
    @endif

    <!-- Card Attributes -->
    <div class="flex flex-wrap gap-2 mt-2">
        @foreach($config['cardAttributes'] as $attribute => $label)
            @if(isset($card[$attribute]) && $card[$attribute])
                <div class="kanban-card-badge bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <span class="font-medium text-primary-600 dark:text-primary-400 mr-1">{{ $label }}:</span>
                    <span>{{ $card[$attribute] }}</span>
                </div>
            @endif
        @endforeach

        <!-- Optional Indicators -->
        @if(isset($card['due_date']))
            <div class="kanban-card-badge {{ strtotime($card['due_date']) < time() ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400' }}">
                <svg class="inline-block w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                {{ $card['due_date'] }}
            </div>
        @endif

        @if(isset($card['assignee']))
            <div class="kanban-card-badge bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400">
                <svg class="inline-block w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                {{ $card['assignee'] }}
            </div>
        @endif
    </div>
</div>
