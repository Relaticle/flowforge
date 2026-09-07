@props(['color', 'total'])

@php
    use Relaticle\Flowforge\Support\ColorResolver;

    $resolvedColor = ColorResolver::resolve($color);
    $isSemantic = ColorResolver::isSemantic($resolvedColor);
    $colorShades = $isSemantic ? null : $resolvedColor;
@endphp

@if($isSemantic)
    <x-filament::badge
        tag="div"
        :color="$resolvedColor"
        {{ $attributes->class(['shrink-0']) }}
    >
        {{ $total }}
    </x-filament::badge>
@else
    <div
        @if($colorShades)
            @style([
                Filament\Support\get_color_css_variables($resolvedColor, shades: [50, 300, 600, 700])
            ])
        @endif
        {{ $attributes->class([
            'shrink-0 items-center border px-2 py-0.5 rounded-md text-xs font-semibold',
            'bg-custom-50 dark:bg-custom-600/20 text-custom-700 dark:text-custom-300 border-custom-700/30 dark:border-custom-300/30' => (bool) $colorShades,
            'bg-gray-50 dark:bg-gray-600/20 text-gray-700 dark:text-gray-300 border-gray-700/30 dark:border-gray-300/30' => ! $colorShades,
        ]) }}>
        {{ $total }}
    </div>
@endif
