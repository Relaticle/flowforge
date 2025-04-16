@props(['label', 'value', 'color' => 'gray', 'icon' => null])

<div
    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium border   group  kanban-color-{{ $color }} transition-colors duration-150">
    @if($icon)
        <x-dynamic-component :component="$icon"
                             class="w-3 h-3 mr-1 text-{{ $color }}-500 dark:text-{{ $color }}-400 group-hover:text-{{ $color }}-600 dark:group-hover:text-{{ $color }}-300"/>
    @endif

    @if($label)
        <span class="font-medium text-{{ $color }}-600 dark:text-{{ $color }}-300 mr-0.5 ">{{ $label }}:</span>
    @endif
    <span class="text-{{ $color }}-800 dark:text-{{ $color }}-200">{{ $value }}</span>
</div>
