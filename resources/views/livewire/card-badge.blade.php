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
        'ff-badge',
        'ff-badge--' . $size,
        'ff-badge--rounded-' . $rounded,
        'kanban-color-' . $color,
        'group' => $icon,
    ];

    $iconClasses = [
        'ff-badge__icon',
        'ff-badge__icon--' . $size,
    ];
@endphp

<div @class($badgeClasses)>
    @if($icon)
        <x-dynamic-component :component="$icon" @class($iconClasses) />
    @endif

    @if($label)
        <span class="ff-badge__label">{{ $label }}@if($value):@endif</span>
    @endif

    @if($value)
        <span class="ff-badge__value">{{ $value }}</span>
    @endif

    @if($badge)
        <span class="ff-badge__count">
            {{ $badge }}
        </span>
    @endif
</div>
