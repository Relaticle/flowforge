<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

describe('shrink empty columns', function () {
    test('is off by default', function () {
        $board = Livewire::test(TestBoard::class)->instance()->getBoard();

        expect($board->shrinksEmpty())->toBeFalse()
            ->and($board->getViewData()['config']['shrinkEmpty'])->toBeFalse();
    });

    test('reaches the view config when enabled', function () {
        $board = Livewire::test(TestBoard::class)->instance()->getBoard()->shrinkEmpty();

        expect($board->shrinksEmpty())->toBeTrue()
            ->and($board->getViewData()['config']['shrinkEmpty'])->toBeTrue();
    });

    test('collapses only empty columns in the rendered board', function () {
        Livewire::test(TestBoard::class)
            ->assertDontSeeHtml('flowforge-column-shrunk');

        // TestBoard has no shrinkEmpty; a shrunk board marks only columns
        // without cards with the slim class.
    });
});
