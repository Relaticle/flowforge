<div 
    {{ $attributes->merge(['class' => 'flowforge-kanban-column']) }}
    data-id="{{ $id }}"
>
    <div class="flowforge-kanban-column-header">
        <h3 class="text-gray-900 dark:text-white">{{ $name }}</h3>
        <span class="flowforge-kanban-column-count">{{ count($items) }}</span>
    </div>
    
    <div class="flowforge-kanban-column-content">
        @forelse($items as $item)
            <x-flowforge.card 
                :id="$item['id']"
                :title="$item['title']"
                :description="$item['description'] ?? null"
                :attributes="collect($item)->except(['id', 'title', 'description'])->toArray()"
                :priority="$item['priority'] ?? null"
            />
        @empty
            <div class="flowforge-kanban-empty-column">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p>No cards in this column</p>
                <p class="text-xs mt-1">Drag cards here from other columns</p>
            </div>
        @endforelse
    </div>
</div>
