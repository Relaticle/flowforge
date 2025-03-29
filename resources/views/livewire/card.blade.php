@props(['config', 'columnId', 'card'])

<div
    class="kanban-card mb-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden cursor-pointer transition-all duration-150 hover:shadow-md"
    x-sortable-handle
    x-sortable-item="{{ $card['id'] }}"
    wire:click="openEditForm('{{ $card['id'] }}', '{{ $columnId }}')"
    x-on:click="$dispatch('open-modal', { id: 'edit-card-modal' })"
>
    <div class="p-3">
        <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $card['title'] }}</h4>

        @if(isset($card['description']) && !empty($card['description']))
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $card['description'] }}</p>
        @endif

        @if(!empty($card['attributes']))
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach($card['attributes'] as $attribute => $label)
                    @if(isset($card['attributes'][$attribute]) && !empty($card['attributes'][$attribute]['value']))
                        <x-flowforge::card-badge
                            :label="$card['attributes'][$attribute]['label']"
                            :value="$card['attributes'][$attribute]['value']"
                            :color="$config->cardAttributeColors[$attribute] ?? 'gray'"
                            :icon="$config->cardAttributeIcons[$attribute] ?? null"
                        />
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
