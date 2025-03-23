@props(['columns', 'config'])

<div
    class="w-full h-full flex flex-col relative"
    x-load
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('flowforge', package: 'relaticle/flowforge'))]"
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flowforge', package: 'relaticle/flowforge') }}"
    x-data="flowforge({
        state: {
            columns: @js($columns),
            statusField: '{{ $config['statusField'] }}',
            recordLabel: '{{ $config['recordLabel'] ?? 'Card' }}',
            pluralRecordLabel: '{{ $config['pluralRecordLabel'] ?? 'Cards' }}'
        }
    })"
>
    <!-- Loading overlay for board operations -->
    <div wire:loading.delay.longer wire:target="updateColumnCards"
         class="absolute inset-0 bg-white/30 dark:bg-gray-900/30 z-10 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 flex items-center gap-3">
            <x-filament::loading-indicator class="h-6 w-6" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Updating board...') }}</span>
        </div>
    </div>

    <!-- Board Content -->
    <div class="flex flex-row h-full w-full overflow-x-auto overflow-y-hidden py-4 px-2 gap-4 kanban-board">
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
