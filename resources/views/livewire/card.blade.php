@props(['columnId', 'record'])

@php
    $processedRecordActions = $this->getBoard()->getBoardRecordActions($record);
    $hasActions = !empty($processedRecordActions);
    $cardAction = $this->getBoard()->getCardAction();
    $hasCardAction = $cardAction !== null;
    $hasPositionIdentifier = $this->getBoard()->getPositionIdentifierAttribute() !== null;

    // A card action configured with ->url() must navigate like a native link instead
    // of mounting a modal, so middle-click, cmd-click and "copy link" keep working.
    //
    // Three conditions keep an action on the Livewire click handler instead:
    //
    // - No explicit ->url(). getUrl() also falls back to the livewire component's
    //   getDefaultActionUrl(), which Filament's resource pages implement for
    //   modal-less Create/Edit/View actions; honouring that here would silently turn
    //   existing boards into links. hasUrl() makes link rendering opt-in.
    // - The action opens a modal. hasModal() only reports an explicit ->modal()
    //   call, so it does not cover ->requiresConfirmation() or a custom modal
    //   heading; shouldOpenModal() does. A whole card is a large accidental-click
    //   target, so a configured confirmation step always wins over a url.
    // - The url is posted to, which needs a form rather than an anchor.
    $cardActionInstance = $this->getBoard()->resolveCardAction($processedRecordActions);
    $cardActionUrl = ($cardActionInstance?->hasUrl()
        && ! $cardActionInstance->shouldOpenModal()
        && ! $cardActionInstance->shouldPostToUrl())
            ? $cardActionInstance->getUrl()
            : null;
    $hasCardActionUrl = filled($cardActionUrl);
    $cardActionHref = $hasCardActionUrl
        ? \Filament\Support\generate_href_html(
            $cardActionUrl,
            $cardActionInstance->shouldOpenUrlInNewTab(),
            hasNestedClickEventHandler: true,
        )
        : null;
@endphp

<div
    @class([
        'flowforge-card mb-3 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-200 hover:shadow-md',
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
        <div class="flex items-start justify-between mb-2">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white p-3"
                @if($hasCardAction && $cardAction && !$hasCardActionUrl)
                    wire:click="mountAction('{{ $cardAction }}', [], @js(['recordKey' => $record['id']]))"
                style="cursor: pointer;"
                @endif
            >
                @if($hasCardActionUrl)
                    <a {{ $cardActionHref }} class="block">
                        {{ $record['title'] }}
                    </a>
                @else
                    {{ $record['title'] }}
                @endif
            </h4>

            @if($hasActions)
                <div class="m-3">
                    <x-filament-actions::group :actions="$processedRecordActions"/>
                </div>
            @endif
        </div>

        <div class="px-3 pb-3"
             @if($hasCardAction && $cardAction && !$hasCardActionUrl)
                 wire:click="mountAction('{{ $cardAction }}', [], @js(['recordKey' => $record['id']]))"
             style="cursor: pointer;"
            @endif
        >
            {{-- Render card schema with compact spacing --}}
            @if(filled($record['schema']))
                @if($hasCardActionUrl)
                    <a {{ $cardActionHref }} class="block">
                        {{ $record['schema'] }}
                    </a>
                @else
                    {{ $record['schema'] }}
                @endif
            @endif
        </div>
    </div>
</div>
