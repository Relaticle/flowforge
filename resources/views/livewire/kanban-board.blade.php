@props(['columns', 'config'])

<div
    class="w-full h-full flex flex-col relative"
    x-load
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('flowforge', package: 'relaticle/flowforge'))]"
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flowforge', package: 'relaticle/flowforge') }}"
    x-data="flowforge({
        state: {
            columns: @js($columns),
            columnField: '{{ $config->getColumnField() }}',
            cardLabel: '{{ $config->cardLabel ?? 'record' }}',
            pluralCardLabel: '{{ $config->pluralCardLabel ?? 'Records' }}'
        }
    })"
>
    <!-- Board Content -->
    <div class="flex-1 overflow-hidden">
        <div class="flex flex-row h-full overflow-x-auto overflow-y-hidden px-2 gap-5 kanban-board pb-4">
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

    <x-flowforge::modals.create-card :config="$config" />
    <x-flowforge::modals.edit-card :config="$config" />
</div>
