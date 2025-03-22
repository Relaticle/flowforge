<div 
    {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-700 rounded-md shadow-sm mb-2 p-3 cursor-grab select-none transition-all duration-200 hover:shadow-md hover:-translate-y-[2px] ' . $getPriorityClass()]) }}
    draggable="true"
    data-id="{{ $id }}"
>
    <div class="text-sm font-medium text-gray-900 dark:text-white mb-1">{{ $title }}</div>
    
    @if($description)
        <div class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $description }}</div>
    @endif
    
    @if(count($attributes) > 0)
        <div class="flex flex-wrap gap-2 mt-2">
            @foreach($attributes as $key => $value)
                @if($value)
                    <div 
                        class="inline-flex items-center text-xs px-2 py-0.5 rounded-full
                            @if($key === 'category') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                            @elseif($key === 'assignee') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300
                            @elseif($key === 'due_date') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                            @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                            @endif"
                    >
                        {{ $value }}
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
