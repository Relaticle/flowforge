@props(['columns', 'config'])

<div
    class="w-full h-full flex flex-col"
    x-load
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('flowforge', package: 'relaticle/flowforge'))]"
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flowforge', package: 'relaticle/flowforge') }}"
    x-data="flowforge({
        state: {
            columns: @js($columns),
            statusField: '{{ $config['statusField'] }}',
        }
    })"
>
    <!-- Board Content -->
    <div class="flex flex-row h-full w-full overflow-x-auto overflow-y-hidden py-4 px-2 gap-4">
        @foreach($columns as $columnId => $column)
            <x-flowforge::kanban.column
                :column-id="$columnId"
                :column="$column"
                :config="$config"
            />
        @endforeach
    </div>

    <x-flowforge::kanban.modals.create-card :config="$config" />
    <x-flowforge::kanban.modals.edit-card :config="$config" />
</div>
