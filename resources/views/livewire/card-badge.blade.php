@props([
    'label' => null,
    'value' => null,
    'color' => 'default',
    'icon' => null,
    'size' => 'md',
    'rounded' => 'md',
    'badge' => null
])

@php
    $isIconOnly = $icon && !$label;
    $hasIcon = filled($icon);
    $hasLabel = filled($label);
    $hasValue = filled($value);
    $hasBadge = filled($badge);
@endphp

<div @class([
    'ff-badge',
    'ff-badge--' . $size,
    'ff-badge--rounded-' . $rounded,
    'kanban-color-' . $color,
    'ff-badge--icon-only' => $isIconOnly,
    'ff-badge--has-icon' => $hasIcon,
])>
    @if($hasIcon)
        <x-filament::icon 
            :icon="$icon" 
            @class(['ff-badge__icon', 'ff-badge__icon--' . $size])
        />
    @endif

    @if($hasLabel)
        <span class="ff-badge__label">
            {{ $label }}@if($hasValue):@endif
        </span>
    @endif

    @if($hasValue)
        <span class="ff-badge__value">{{ $value }}</span>
    @endif

    @if($hasBadge)
        <span class="ff-badge__count">{{ $badge }}</span>
    @endif
</div>
