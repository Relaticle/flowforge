@props(['label', 'value', 'color' => 'gray', 'icon' => null])

<div class="inline-flex items-center rounded-full bg-{{ $color }}-100 dark:bg-{{ $color }}-900 px-2 py-1 text-xs font-medium text-{{ $color }}-700 dark:text-{{ $color }}-300 ring-1 ring-inset ring-{{ $color }}-400/20 dark:ring-{{ $color }}-800/50">
    @if($icon)
        <x-dynamic-component :component="$icon" class="w-3 h-3 mr-1" />
    @endif
    <span>{{ $label }}: {{ $value }}</span>
</div>
