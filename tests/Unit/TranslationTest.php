<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

test('the German empty column hint keeps the card label capitalised', function () {
    app()->setLocale('de');

    $html = Blade::render('<x-flowforge::empty-column :pluralCardLabel="$label" />', [
        'label' => __('flowforge::flowforge.plural_card_label'),
    ]);

    expect($html)->toContain('Keine Datensätze in dieser Spalte');
});

test('the empty column hint preserves each bundled locale', function (string $locale, string $hint): void {
    app()->setLocale($locale);

    $html = Blade::render('<x-flowforge::empty-column :pluralCardLabel="$label" />', [
        'label' => __('flowforge::flowforge.plural_card_label'),
    ]);

    expect($html)->toContain($hint);
})->with([
    'English' => ['en', 'No records in this column'],
    'German' => ['de', 'Keine Datensätze in dieser Spalte'],
    'Spanish' => ['es', 'No hay registros en esta columna'],
    'Dutch' => ['nl', 'Geen records in deze kolom'],
]);
