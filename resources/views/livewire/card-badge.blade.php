@props([
    'label' => null,
    'value' => null,
    'color' => 'default',
    'icon' => null,
    'size' => 'md',
    'type' => null,
    'rounded' => 'md',
    'badge' => null
])

@php
    $badgeClasses = [
        'inline-flex items-center transition-colors duration-150',
        'text-xs' => $size === 'sm' || $size === 'md',
        'text-sm' => $size === 'lg',
        'font-medium',
        'kanban-color-' . $color,
        'rounded-full' => $rounded === 'full',
        'rounded-md' => $rounded === 'md',
        'py-0.5 px-2' => $size === 'md',
        'py-0 px-1.5' => $size === 'sm',
        'py-1 px-2.5' => $size === 'lg',
        'group' => $icon,
    ];

    $iconClasses = [
        'w-3 h-3' => $size === 'sm',
        'w-3.5 h-3.5' => $size === 'md',
        'w-4 h-4' => $size === 'lg',
        'mr-1' => $size === 'sm',
        'mr-1.5' => $size === 'md' || $size === 'lg',
        'flex-shrink-0',
    ];
@endphp

<div @class($badgeClasses)>
    @if($icon)
        <x-dynamic-component :component="$icon" @class($iconClasses) />
    @endif

    @if($label)
        <span class="font-medium mr-0.5">{{ $label }}@if($value):@endif</span>
    @endif

    @if($value)
        <span>{{ $value }}</span>
    @endif

    @if($badge)
        <span class="ml-1.5 flex items-center justify-center w-4 h-4 rounded-full bg-white bg-opacity-30 text-xs leading-none">
            {{ $badge }}
        </span>
    @endif
</div>
