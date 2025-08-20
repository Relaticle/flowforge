@props(['pluralCardLabel'])

<div class="ff-empty-column">
    <x-filament::icon 
        icon="heroicon-o-archive-box" 
        class="ff-empty-column__icon" 
    />
    <p class="ff-empty-column__text">
        {{ __('flowforge::flowforge.no_cards_in_column', ['cardLabel' => strtolower($pluralCardLabel)]) }}
    </p>
</div>
