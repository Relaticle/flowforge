@php
    use Filament\Actions\Action;
    use Filament\Actions\ActionGroup;
    use Filament\Schemas\Components\Component;

    $gap = $getGap();
    $wrap = $shouldWrap();
    $justify = $getJustify();
    $align = $getAlign();
@endphp

<div
    {{
        $attributes
            ->merge($getExtraAttributes(), escape: false)
            ->class([
                'flex',
                'flex-row',
                // Gap classes optimized for cards
                'gap-1' => $gap === 'xs',
                'gap-2' => $gap === 'sm',
                'gap-3' => $gap === 'md',
                'gap-4' => $gap === 'lg',
                // Wrap settings
                'flex-wrap' => $wrap,
                'flex-nowrap' => !$wrap,
                // Justify settings
                'justify-start' => $justify === 'start',
                'justify-center' => $justify === 'center',
                'justify-end' => $justify === 'end',
                'justify-between' => $justify === 'between',
                'justify-around' => $justify === 'around',
                'justify-evenly' => $justify === 'evenly',
                // Align settings
                'items-start' => $align === 'start',
                'items-center' => $align === 'center',
                'items-end' => $align === 'end',
                'items-baseline' => $align === 'baseline',
                'items-stretch' => $align === 'stretch',
            ])
    }}
>
    @foreach ($getChildSchema()->getComponents() as $component)
        @if (($component instanceof Action) || ($component instanceof ActionGroup))
            <div class="flex-shrink-0">
                {{ $component }}
            </div>
        @else
            @php
                $hiddenJs = $component->getHiddenJs();
                $visibleJs = $component->getVisibleJs();
                $componentStatePath = $component->getStatePath();
            @endphp

            <div
                x-data="filamentSchemaComponent({
                    path: @js($componentStatePath),
                    containerPath: @js($statePath),
                    isLive: @js($schemaComponent->isLive()),
                    $wire,
                })"
                @if ($afterStateUpdatedJs = $schemaComponent->getAfterStateUpdatedJs())
                    x-init="{!! implode(';', array_map(
                        fn (string $js): string => '$wire.watch(' . Js::from($componentStatePath) . ', ($state, $old) => ($state !== undefined) && eval(' . Js::from($js) . '))',
                        $afterStateUpdatedJs,
                    )) !!}"
                @endif
                @if (filled($visibilityJs = match ([filled($hiddenJs), filled($visibleJs)]) {
                     [true, true] => "(! ({$hiddenJs})) && ({$visibleJs})",
                     [true, false] => "! ({$hiddenJs})",
                     [false, true] => $visibleJs,
                     default => null,
                 }))
                    x-bind:class="{ 'fi-hidden': ! ({!! $visibilityJs !!}) }"
                    x-cloak
                @endif
                @class([
                    'flex-shrink-0' => !($component instanceof Component && $component->canGrow()),
                    'flex-grow' => ($component instanceof Component) && $component->canGrow(),
                ])
            >
                {{ $component }}
            </div>
        @endif
    @endforeach
</div>
