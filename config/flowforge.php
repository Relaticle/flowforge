<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Flowforge Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the Flowforge package.
    |
    */

    // Default column settings
    'columns' => [
        'default_limit' => 10, // Maximum number of items per column
    ],

    // Kanban board settings
    'kanban' => [
        'initial_cards_count' => 20, // Initial number of cards to show per column
        'cards_increment' => 10, // Number of cards to load when loading more
        'max_cards_per_column' => 100, // Maximum number of cards that can be loaded per column
    ],

    // User Interface settings
    'ui' => [
        'show_item_counts' => true, // Whether to show item counts in column headers
        'show_board_title' => true, // Whether to show the board title
        'show_refresh_button' => true, // Whether to show the refresh button
    ],

    // Animation settings
    'animations' => [
        'enable_drag_animations' => true, // Whether to enable animations during drag operations
    ],
];
