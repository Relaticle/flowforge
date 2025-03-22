@props(['config'])

<div
    class="bg-white dark:bg-gray-700 rounded-md shadow-sm mb-2 p-3 cursor-pointer select-none transition-all duration-200 hover:shadow-md hover:-translate-y-[2px]"
    :class="{
        'border-l-4 border-red-500': card.priority === 'high',
        'border-l-4 border-yellow-500': card.priority === 'medium',
        'border-l-4 border-green-500': card.priority === 'low',
        'border-l-4 border-gray-300 dark:border-gray-600': !card.priority
    }"
    draggable="true"
    x-on:dragstart="
        $event.dataTransfer.setData('text/plain', JSON.stringify({
            id: card.id,
            sourceColumn: columnId
        }));
        $event.target.classList.add('opacity-50');
    "
    x-on:dragend="$event.target.classList.remove('opacity-50')"
    x-on:click.stop="openEditModal(card, columnId)"
>
    <div class="text-sm font-medium text-gray-900 dark:text-white mb-1" x-text="card.title"></div>

    <template x-if="card.description">
        <div class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2" x-text="card.description"></div>
    </template>

    <div class="flex flex-wrap gap-2 mt-2">
        <template x-for="(value, key) in card" :key="key">
            <template x-if="!['id', 'title', 'description'].includes(key) && value">
                <x-flowforge::kanban.card-badge />
            </template>
        </template>
    </div>
</div>
