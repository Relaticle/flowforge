@php use Filament\Support\Facades\FilamentAsset; @endphp
@props(['columns', 'config'])

<div
    class="ff-board"
    x-load
    x-load-css="[@js(FilamentAsset::getStyleHref('flowforge', package: 'relaticle/flowforge'))]"
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('flowforge', package: 'relaticle/flowforge') }}"
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

    <x-filament-actions::modals/>
</div>
