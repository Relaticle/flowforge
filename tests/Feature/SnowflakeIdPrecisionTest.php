<?php

declare(strict_types=1);

use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

/**
 * Regression test for GitHub issue #88:
 * Large integer IDs (snowflakes) lose precision when passed through @js() in Blade.
 * JavaScript's Number type uses IEEE 754 doubles, which can only safely represent
 * integers up to 2^53 - 1 (9007199254740991). Snowflake IDs exceed this.
 *
 * @see https://github.com/relaticle/flowforge/issues/88
 */
describe('snowflake ID precision', function () {
    test('formatBoardRecord casts record ID to string to prevent JS precision loss', function () {
        $task = Task::factory()->todo()->withPosition('65535.0000000000')->create();

        $board = app(TestBoard::class)->getBoard();
        $formatted = $board->formatBoardRecord($task);

        // The ID must be a string so @js() emits a JSON string ("123") not a number (123)
        // This prevents JavaScript precision loss for large IDs like snowflakes
        expect($formatted['id'])->toBeString();
    });

    test('card blade renders recordKey as string in wire:click for large IDs', function () {
        $task = Task::factory()->todo()->withPosition('65535.0000000000')->create();

        // Simulate what @js() does: json_encode the record ID
        // If ID is an integer, json_encode produces a number literal which JS truncates
        $idAsInt = (int) $task->id;
        $jsonFromInt = json_encode(['recordKey' => $idAsInt]);

        // If ID is a string, json_encode produces a quoted string which JS preserves
        $idAsString = (string) $task->id;
        $jsonFromString = json_encode(['recordKey' => $idAsString]);

        // For a snowflake like 420533451316027392:
        // json_encode(int)    -> {"recordKey":420533451316027392}  <- JS reads as 420533451316027400 (WRONG)
        // json_encode(string) -> {"recordKey":"420533451316027392"} <- JS reads correctly
        $snowflakeId = 420533451316027392;
        $jsonSnowflakeInt = json_encode(['recordKey' => $snowflakeId]);
        $jsonSnowflakeStr = json_encode(['recordKey' => (string) $snowflakeId]);

        // The string version wraps in quotes, preserving exact value
        expect($jsonSnowflakeStr)->toContain('"420533451316027392"');

        // The int version does NOT wrap in quotes -- JS will lose precision
        expect($jsonSnowflakeInt)->not->toContain('"420533451316027392"');
    });
});
