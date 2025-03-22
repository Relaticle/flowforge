<div 
    {{ $attributes->merge(['class' => 'flex flex-col h-full min-w-64 w-64 bg-gray-100 dark:bg-gray-800 rounded-xl shadow-sm p-2']) }}
    data-id="{{ $id }}"
>
    <div class="flex items-center justify-between p-2 mb-2 font-medium">
        <h3 class="text-gray-900 dark:text-white">{{ $name }}</h3>
        <span class="flex items-center justify-center h-6 min-w-6 px-1.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-full">{{ count($items) }}</span>
    </div>
    
    <div class="flex-1 overflow-y-auto overflow-x-hidden p-1">
        @forelse($items as $item)
            <x-flowforge.card 
                :id="$item['id']"
                :title="$item['title']"
                :description="$item['description'] ?? null"
                :attributes="collect($item)->except(['id', 'title', 'description'])->toArray()"
                :priority="$item['priority'] ?? null"
            />
        @empty
            <div class="flex flex-col items-center justify-center text-center p-4 text-sm text-gray-400 dark:text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p>No cards in this column</p>
                <p class="text-xs mt-1">Drag cards here from other columns</p>
            </div>
        @endforelse
    </div>
</div>
