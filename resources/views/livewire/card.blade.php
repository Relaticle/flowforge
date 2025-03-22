@props(['config', 'card'])

<div
    x-sortable-handle
    x-sortable-item="{{ $card['id'] }}"
    class="bg-white dark:bg-gray-700 rounded-md shadow-sm mb-2 p-3  transition-all duration-200 hover:shadow-md hover:-translate-y-[2px]"
>
    <div class="text-sm font-medium text-gray-900 dark:text-white mb-1">{{ $card['title'] }}</div>

{{--    <template -if="card.description">--}}
{{--        <div class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2" x-text="card.description"></div>--}}
{{--    </template>--}}

{{--    <div class="flex flex-wrap gap-2 mt-2">--}}
{{--        <template x-for="(value, key) in card" :key="key">--}}
{{--            <template x-if="!['id', 'title', 'description'].includes(key) && value">--}}
{{--                <x-flowforge::kanban.card-badge />--}}
{{--            </template>--}}
{{--        </template>--}}
{{--    </div>--}}
</div>
