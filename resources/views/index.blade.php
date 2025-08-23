@php use Filament\Support\Facades\FilamentAsset; @endphp
@props(['columns', 'config'])

<div
    class="w-full h-full flex flex-col relative"
    x-load
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('flowforge', package: 'relaticle/flowforge') }}"
    x-data="flowforge({
        state: {
            columns: @js($columns),
            titleField: '{{ $config->getTitleField() }}',
            columnField: '{{ $config->getColumnField() }}',
            cardLabel: '{{ $config->getSingularCardLabel() }}',
            pluralCardLabel: '{{ $config->getPluralCardLabel() }}'
        }
    })"
>

    @include('flowforge::components.filters')

    <!-- Board Content -->
    <div class="flex-1 overflow-hidden">
        <div class="flex flex-row h-full overflow-x-auto overflow-y-hidden px-2 gap-5 pb-4">
            @foreach($columns as $columnId => $column)
                <x-flowforge::column
                    :columnId="$columnId"
                    :column="$column"
                    :config="$config"
                    wire:key="column-{{ $columnId }}"
                />
            @endforeach
        </div>
    </div>

    <x-filament-actions::modals/>
</div>
