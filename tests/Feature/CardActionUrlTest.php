<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;
use Relaticle\Flowforge\Tests\Fixtures\TestCardActionBoard;
use Relaticle\Flowforge\Tests\Fixtures\TestCardActionModalBoard;
use Relaticle\Flowforge\Tests\Fixtures\TestCardActionNewTabBoard;

/**
 * A card action configured with ->url() must render the card as a native link, so
 * middle-click, cmd-click and "copy link address" keep working, instead of always
 * mounting an action modal.
 *
 * @see https://github.com/relaticle/flowforge/issues/164
 */
beforeEach(function () {
    $this->task = Task::factory()->create(['status' => 'todo', 'title' => 'Linked task']);
});

/**
 * The card title is the click target the board wires up, so assertions have to look
 * inside it: the actions dropdown renders its own anchor for the same action and
 * would satisfy a whole-page assertion even when the card itself is not a link.
 */
function cardTitleHtml(string $html): string
{
    return str($html)
        ->after('<h4 class="text-sm font-semibold')
        ->before('</h4>')
        ->toString();
}

test('card action with a url renders the card title as a link', function () {
    $title = cardTitleHtml(Livewire::test(TestCardActionBoard::class)->html());

    expect($title)->toContain('href="https://example.test/tasks/' . $this->task->getKey() . '"');
});

test('card action with a url does not mount an action on click', function () {
    $html = Livewire::test(TestCardActionBoard::class)->html();

    expect($html)->not->toContain("mountAction('view'");
});

test('card action honours openUrlInNewTab', function () {
    $html = Livewire::test(TestCardActionNewTabBoard::class)->html();

    expect(cardTitleHtml($html))
        ->toContain('href="https://example.test/tasks/' . $this->task->getKey() . '" target="_blank"')
        ->and($html)->not->toContain("mountAction('viewInNewTab'");
});

test('card action without a url still mounts the action', function () {
    $title = cardTitleHtml(Livewire::test(TestCardActionModalBoard::class)->html());

    expect($title)->toContain("mountAction('edit'")
        ->and($title)->not->toContain('href=');
});

test('resolveCardAction finds the configured action among record actions', function () {
    $board = Livewire::test(TestCardActionBoard::class)->instance()->getBoard();

    $record = ['id' => $this->task->getKey(), 'model' => $this->task];

    expect($board->resolveCardAction($board->getBoardRecordActions($record))?->getName())->toBe('view');
});

test('resolveCardAction returns null when no card action is configured', function () {
    $board = Livewire::test(TestBoard::class)->instance()->getBoard();

    expect($board->resolveCardAction([]))->toBeNull();
});
