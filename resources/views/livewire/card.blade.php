@props(['config', 'columnId', 'record'])

@php
    $processedRecordActions = $this->getCardActionsForRecord($record);
    $hasActions = !empty($processedRecordActions);
    $cardAction = $this->getCardActionForRecord($record);
    $hasCardAction = $this->hasCardAction($record);
@endphp

<div
    @class([
        'ff-card kanban-card',
        'ff-card--interactive' => $hasActions || $hasCardAction,
        'ff-card--clickable' => $hasCardAction,
        'ff-card--non-interactive' => !$hasActions && !$hasCardAction,
        'ff-card--no-reorder' => $this->getBoard()->getReorderBy() === null
    ])
    @if($this->getBoard()->getReorderBy() !== null)
        x-sortable-handle
        x-sortable-item="{{ $record['id'] }}"
    @endif
    @if($hasCardAction && $cardAction)
        wire:click="mountAction('{{ $cardAction }}', [], @js(['recordKey' => $record['id']]))"
        style="cursor: pointer;"
    @endif
>
    <div class="ff-card__content">
        <div class="ff-card__header">
            <h4 class="ff-card__title">{{ $record['title'] }}</h4>

            {{-- Render record actions --}}
            @if($hasActions)
                <div class="ff-card__actions" @if($hasCardAction) @click.stop @endif>
                    <x-filament-actions::group :actions="$processedRecordActions" />
                </div>
            @endif
        </div>

        {{-- Render card schema if configured --}}
        @if(isset($record['schema_html']) && !empty($record['schema_html']))
            <div class="ff-card__schema">
                {!! $record['schema_html'] !!}
            </div>
        @endif
    </div>
</div>
