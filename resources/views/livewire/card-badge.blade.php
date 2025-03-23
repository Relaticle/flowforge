@props(['label' => null, 'value', 'color' => 'gray', 'icon' => null])

<div @class([
    "kanban-card-badge",
    "bg-{$color}-100 text-{$color}-700 dark:bg-{$color}-900/40 dark:text-{$color}-400" => $color !== 'gray',
    "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300" => $color === 'gray',
])>
    @if($icon)
        <svg class="inline-block w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            {!! $icon !!}
        </svg>
    @endif

    @if($label)
        <span class="font-medium text-primary-600 dark:text-primary-400 mr-1">{{ $label }}:</span>
    @endif

    <span>{{ $value }}</span>
</div>
