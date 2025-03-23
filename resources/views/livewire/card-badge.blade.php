@props(['label' => null, 'value', 'color' => 'gray', 'icon' => null])

<div
    class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
    <span class="font-medium mr-1">{{ $label }}:</span>
    <span>{{ $value }}</span>
</div>
