@props(['columnId', 'record'])

@php
    $processedRecordActions = $this->getBoard()->getBoardRecordActions($record);
    $hasActions = !empty($processedRecordActions);
    $cardAction = $this->getBoard()->getCardAction();
    $hasCardAction = $cardAction !== null;
    $hasPositionIdentifier = $this->getBoard()->getPositionIdentifierAttribute() !== null;
@endphp

<div
    @class([
        'flowforge-card mb-2 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-200 hover:shadow-md max-w-[300px]',
        'cursor-pointer' => $hasActions || $hasCardAction,
        'cursor-pointer transition-all duration-100 ease-in-out hover:shadow-lg hover:border-gray-400 active:shadow-md' => $hasCardAction,
        'cursor-grab hover:cursor-grabbing' => $hasPositionIdentifier,
        'cursor-default' => !$hasActions && !$hasCardAction && !$hasPositionIdentifier,
    ])
    x-sortable-item="{{ $record['id'] }}"
    data-card-id="{{ $record['id'] }}"
    @if($hasPositionIdentifier)
        x-sortable-handle
    @endif
    data-position="{{ $record['position'] ?? '' }}"
>
    <div class="flowforge-card-content">
        <div class="flex items-start justify-between">
            <h4 class="text-xs font-semibold text-gray-900 dark:text-white px-3 pt-2"
                @if($hasCardAction && $cardAction)
                    wire:click="mountAction('{{ $cardAction }}', [], @js(['recordKey' => $record['id']]))"
                style="cursor: pointer;"
                @endif
            >
                {{ $record['title'] }}
            </h4>

            @if($hasActions)
                <div class="mt-1 mr-1">
                    <x-filament-actions::group :actions="$processedRecordActions"/>
                </div>
            @endif
        </div>

        <div class="px-3 pb-2"
             @if($hasCardAction && $cardAction)
                 wire:click="mountAction('{{ $cardAction }}', [], @js(['recordKey' => $record['id']]))"
             style="cursor: pointer;"
            @endif
        >
            {{-- Render card schema with compact spacing --}}
            @if(filled($record['schema']))
                <div class="flowforge-card-schema">
                    {{ $record['schema'] }}
                </div>
            @endif
        </div>
    </div>
</div>

@once
<style>
    /* Collapse Filament's default gap-6 between schema entries inside cards */
    .flowforge-card-schema .fi-sc.fi-sc-has-gap {
        gap: 0;
    }
    /* Remove internal gap within each entry wrapper */
    .flowforge-card-schema .fi-in-entry {
        gap: 0;
    }
</style>
@endonce
