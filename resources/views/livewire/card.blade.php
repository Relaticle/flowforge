@props(['config', 'columnId', 'record'])

<div
    @class([
        'kanban-card mb-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-150 hover:shadow-md',
        'cursor-pointer' => $this->editAction() &&  ($this->editAction)(['record' => $record['id']])->isVisible(),
        'cursor-default' => !$this->editAction()
    ])
    x-sortable-handle
    x-sortable-item="{{ $record['id'] }}"
    @if($this->editAction() &&  ($this->editAction)(['record' => $record['id']])->isVisible())
        wire:click="mountAction('edit', {record: {{ $record['id'] }}})"
    @endif
>
    <div class="p-3">
        <h4 class="text-sm font-medium text-gray-900 dark:text-white card-title">{{ $record['title'] }}</h4>

        @if(!empty($record['description']))
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $record['description'] }}</p>
        @endif

        @if(collect($record['attributes'] ?? [])->filter(fn($attribute) => !empty($attribute['value']))->isNotEmpty())

            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach($record['attributes'] as $attribute => $data)
                    @if(isset($data) && !empty($data['value']))
                        <x-flowforge::card-badge
                            :label="$data['label']"
                            :value="$data['value']"
                            :color="$data['color'] ?? 'default'"
                            :icon="$data['icon'] ?? null"
                            :type="$data['type'] ?? null"
                            :badge="$data['badge'] ?? null"
                            :rounded="$data['rounded'] ?? 'md'"
                            :size="$data['size'] ?? 'md'"
                        />
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
