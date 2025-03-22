<div
    class="inline-flex items-center text-xs px-2 py-0.5 rounded-full"
    :class="{
        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': key === 'category',
        'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300': key === 'assignee',
        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': key === 'due_date',
        'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300': !['category', 'assignee', 'due_date'].includes(key)
    }"
>
    <span x-text="value"></span>
</div>
