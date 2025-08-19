@props(['pluralCardLabel'])

<div class="ff-empty-column">
    <x-filament::icon 
        icon="heroicon-o-archive-box" 
        class="ff-empty-column__icon" 
    />
    <p class="ff-empty-column__text">
        {{ __('No :cardLabel in this column', ['cardLabel' => strtolower($pluralCardLabel)]) }}
    </p>
</div>
