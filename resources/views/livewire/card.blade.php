@props(['config', 'columnId', 'record'])

@php
    $processedRecordActions = $this->getCardActionsForRecord($record);
    $hasActions = !empty($processedRecordActions);
    $cardAction = $this->getCardActionForRecord($record);
    $hasCardAction = $this->hasCardAction($record);
@endphp

<div
    @class([
        'mb-3 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden transition-all duration-150 hover:shadow-md',
        'cursor-pointer' => $hasActions || $hasCardAction,
        'cursor-pointer transition-all duration-200 ease-in-out hover:transform hover:-translate-y-0.5 hover:shadow-lg hover:ring-1 hover:ring-primary-200 dark:hover:ring-primary-800/30 active:transform active:translate-y-0 active:shadow-md' => $hasCardAction,
        'cursor-default' => !$hasActions && !$hasCardAction,
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
    <div class="p-3">
        <div class="flex items-center justify-between mb-2">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $record['title'] }}</h4>

            {{-- Render record actions --}}
            @if($hasActions)
                <div class="relative z-10" @if($hasCardAction) @click.stop @endif>
                    <x-filament-actions::group :actions="$processedRecordActions" />
                </div>
            @endif
        </div>

        {{-- Render card schema with compact spacing --}}
        @if(isset($record['schema_html']) && !empty($record['schema_html']))
            <div class="space-y-1 [&_.fi-sc-flex]:gap-2 [&_.fi-sc-flex.fi-dense]:gap-1 [&_.fi-in-entry]:gap-y-0.5 [&_.fi-in-entry-content-ctn]:gap-x-1.5">
                {!! $record['schema_html'] !!}
            </div>
        @endif
    </div>
</div>
