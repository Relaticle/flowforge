<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Panel;
use Livewire\Livewire;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;
use Relaticle\Flowforge\Tests\Fixtures\TestBoardResourcePage;
use Relaticle\Flowforge\Tests\Fixtures\TestResource;
use Relaticle\Flowforge\Tests\Fixtures\TestStandaloneBoard;

describe('card labels', function () {
    test('names the cards after the resource of the board page', function () {
        $board = Livewire::test(TestBoardResourcePage::class)->instance()->getBoard();

        expect($board->getCardLabel())->toBe(TestResource::getModelLabel())
            ->and($board->getPluralCardLabel())->toBe(TestResource::getPluralModelLabel());
    });

    test('names the cards after the resource registered for the model', function (): void {
        $resource = new class extends TestResource
        {
            protected static ?string $modelLabel = 'Support ticket';

            protected static ?string $pluralModelLabel = 'Support tickets';
        };

        Filament::setCurrentPanel(Panel::make()->id('labels')->resources([$resource::class]));

        $component = Livewire::test(TestStandaloneBoard::class)
            ->assertSee('No support tickets in this column');

        $board = $component->instance()->getBoard();

        expect($board->getCardLabel())->toBe('Support ticket')
            ->and($board->getPluralCardLabel())->toBe('Support tickets');
    });

    test('explicit labels win over the model', function () {
        $board = Livewire::test(TestBoard::class)->instance()->getBoard()
            ->cardLabel('Ticket')
            ->pluralCardLabel('Tickets');

        expect($board->getCardLabel())->toBe('Ticket')
            ->and($board->getPluralCardLabel())->toBe('Tickets');
    });

    test('falls back to the translated label without a query', function () {
        $board = Livewire::test(TestBoard::class)->instance()->getBoard()->query(fn () => null);

        expect($board->getCardLabel())->toBe(__('flowforge::flowforge.card_label'))
            ->and($board->getPluralCardLabel())->toBe(__('flowforge::flowforge.plural_card_label'));
    });

    test('the empty column names the cards', function () {
        Livewire::test(TestBoard::class)
            ->assertStatus(200)
            ->assertSee('No tasks in this column');
    });

    test('serializes custom labels safely into the rendered board', function (string $label): void {
        Board::configureUsing(
            fn (Board $board): Board => $board->cardLabel($label)->pluralCardLabel($label),
            during: function () use ($label): void {
                $html = Livewire::test(TestBoard::class)->html();
                expect(preg_match('/x-data="(flowforge\([\s\S]*?)"/', $html, $attributes))->toBe(1);

                $expression = html_entity_decode($attributes[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                foreach (['cardLabel', 'pluralCardLabel'] as $key) {
                    expect(preg_match("/{$key}:\\s*'((?:[^'\\\\]|\\\\.)*)'/u", $expression, $matches))->toBe(1);
                    expect(json_decode('"' . $matches[1] . '"', flags: JSON_THROW_ON_ERROR))->toBe($label);
                }
            },
        );
    })->with([
        'apostrophe' => "Manager's task",
        'HTML and quotes' => '<New> "customer" & contacts',
        'backslash and newline' => "Sales\\Support\nTasks",
        'Unicode' => '顧客',
    ]);

    test('evaluates translated label closures for the current locale', function (): void {
        Board::configureUsing(
            fn (Board $board): Board => $board
                ->cardLabel(fn (): string => __('flowforge::flowforge.card_label'))
                ->pluralCardLabel(fn (): string => __('flowforge::flowforge.plural_card_label')),
            during: function (): void {
                app()->setLocale('de');

                Livewire::test(TestBoard::class)->assertSee('Keine Datensätze in dieser Spalte');

                app()->setLocale('es');

                Livewire::test(TestBoard::class)->assertSee('No hay registros en esta columna');
            },
        );
    });
});
