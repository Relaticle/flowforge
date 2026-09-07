<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

describe('collapse empty columns', function () {
    test('is off by default', function () {
        Task::factory()->todo()->create();

        $board = Livewire::test(TestBoard::class)->instance()->getBoard();

        expect($board->collapsesEmptyColumns())->toBeFalse()
            ->and($board->getViewData()['config']['collapseEmptyColumns'])->toBeFalse();
    });

    test('renders no collapsed column while the option is off', function () {
        Task::factory()->todo()->create(['title' => 'Only Task']);

        Livewire::test(TestBoard::class)
            ->assertStatus(200)
            ->assertDontSeeHtml('flowforge-column-collapsed');
    });

    test('collapses only the columns holding no cards', function () {
        Task::factory()->todo()->create(['title' => 'Only Task']);

        $html = Livewire::test(TestBoard::class, ['collapseEmpty' => true])
            ->assertStatus(200)
            ->assertSee('Only Task')
            ->html();

        expect(substr_count($html, 'flowforge-column-collapsed'))->toBe(2);
    });

    test('leaves every column expanded when the whole board is empty', function () {
        Livewire::test(TestBoard::class, ['collapseEmpty' => true])
            ->assertStatus(200)
            ->assertDontSeeHtml('flowforge-column-collapsed');
    });

    test('ignores cards parked in a hidden column when deciding to collapse', function () {
        Task::factory()->create(['status' => 'archived', 'title' => 'Archived Task']);

        Livewire::test(TestBoard::class, ['collapseEmpty' => true])
            ->assertStatus(200)
            ->assertDontSeeHtml('flowforge-column-collapsed');
    });

    test('leaves every column expanded when a search matches nothing', function () {
        Task::factory()->todo()->create(['title' => 'Only Task']);

        Livewire::test(TestBoard::class, ['collapseEmpty' => true])
            ->assertSeeHtml('flowforge-column-collapsed')
            ->set('tableSearch', 'no-such-card')
            ->assertDontSeeHtml('flowforge-column-collapsed');
    });

    test('a collapsed column carries the drag-expand binding', function () {
        Task::factory()->todo()->create(['title' => 'Only Task']);

        Livewire::test(TestBoard::class, ['collapseEmpty' => true])
            ->assertStatus(200)
            ->assertSeeHtml("'w-[300px] min-w-[300px]': dragOverColumn ===");
    });
});
