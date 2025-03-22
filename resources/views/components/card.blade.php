<div 
    {{ $attributes->merge(['class' => 'flowforge-kanban-card ' . $getPriorityClass()]) }}
    draggable="true"
    data-id="{{ $id }}"
>
    <div class="flowforge-kanban-card-title">{{ $title }}</div>
    
    @if($description)
        <div class="flowforge-kanban-card-description">{{ $description }}</div>
    @endif
    
    @if(count($attributes) > 0)
        <div class="flowforge-kanban-card-attributes">
            @foreach($attributes as $key => $value)
                @if($value)
                    <div 
                        class="flowforge-kanban-card-attribute
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
