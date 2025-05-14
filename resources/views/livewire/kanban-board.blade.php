@props(['columns', 'config'])

<div
    class="ff-board"
    x-load
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('flowforge', package: 'relaticle/flowforge'))]"
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flowforge', package: 'relaticle/flowforge') }}"
    x-data="flowforge({
        state: {
            columns: @js($columns),
            titleField: '{{ $config->getTitleField() }}',
            descriptionField: '{{ $config->getDescriptionField() }}',
            columnField: '{{ $config->getColumnField() }}',
            cardLabel: '{{ $config->getSingularCardLabel() }}',
            pluralCardLabel: '{{ $config->getPluralCardLabel() }}'
        }
    })"
>
    <!-- Filter UI Section -->
    @if(count($config->getFilterableFields()))
        <div class="ff-board__filters">
            @include('flowforge::livewire.kanban-board-filters')
        </div>
    @endif

    <!-- Active Filters Indicator -->
    @if(count($filters))
        <div class="mb-4 px-4 py-2 bg-primary-50 rounded-md text-sm text-primary-700">
            {{ __('Viewing filtered results.') }} <button wire:click="resetFilters" class="underline">{{ __('Clear all filters') }}</button>
        </div>
    @endif

    <!-- Board Content -->
    <div class="ff-board__content">
        <div class="ff-board__columns kanban-board">
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

    <x-filament-actions::modals />
</div>
