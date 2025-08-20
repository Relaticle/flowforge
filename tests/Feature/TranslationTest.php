<?php

use Illuminate\Support\Facades\App;
use Relaticle\Flowforge\FlowforgeServiceProvider;

test('translation files exist for supported languages', function () {
    $englishPath = __DIR__ . '/../../resources/lang/en/flowforge.php';
    $dutchPath = __DIR__ . '/../../resources/lang/nl/flowforge.php';

    expect(file_exists($englishPath))->toBeTrue();
    expect(file_exists($dutchPath))->toBeTrue();
});

test('english translation file contains required keys', function () {
    $translations = require __DIR__ . '/../../resources/lang/en/flowforge.php';

    $requiredKeys = [
        'loading_more_cards',
        'no_cards_in_column',
        'cards_count',
    ];

    foreach ($requiredKeys as $key) {
        expect($translations)->toHaveKey($key)
            ->and($translations[$key])->not->toBeEmpty();
    }
});

test('dutch translation file contains same keys as english', function () {
    $englishTranslations = require __DIR__ . '/../../resources/lang/en/flowforge.php';
    $dutchTranslations = require __DIR__ . '/../../resources/lang/nl/flowforge.php';

    expect(array_keys($dutchTranslations))->toEqual(array_keys($englishTranslations));
});

test('translation keys resolve correctly', function () {
    App::setLocale('en');

    expect(__('flowforge::flowforge.loading_more_cards'))->toBe('Loading more cards...')
        ->and(__('flowforge::flowforge.no_cards_in_column', ['cardLabel' => 'tasks']))->toBe('No tasks in this column');
});

test('dutch translations resolve correctly', function () {
    App::setLocale('nl');

    expect(__('flowforge::flowforge.loading_more_cards'))->toBe('Meer kaarten laden...')
        ->and(__('flowforge::flowforge.no_cards_in_column', ['cardLabel' => 'taken']))->toBe('Geen taken in deze kolom');
});

test('card count pluralization works correctly', function () {
    App::setLocale('en');

    // Test singular
    $singular = trans_choice('flowforge::flowforge.cards_count', 1, ['card' => 'card', 'cards' => 'cards']);
    expect($singular)->toBe('card');

    // Test plural
    $plural = trans_choice('flowforge::flowforge.cards_count', 5, ['card' => 'card', 'cards' => 'cards']);
    expect($plural)->toBe('cards');

    // Test zero
    $zero = trans_choice('flowforge::flowforge.cards_count', 0, ['card' => 'card', 'cards' => 'cards']);
    expect($zero)->toBe('cards');
});

test('service provider has translation support enabled', function () {
    // Check if the service provider enables translations via hasTranslations()
    $serviceProviderPath = __DIR__ . '/../../src/FlowforgeServiceProvider.php';
    $content = file_get_contents($serviceProviderPath);

    expect($content)->toContain('$package->hasTranslations()');
});
