<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Relaticle\Flowforge\Concerns\InteractsWithBoard;
use Relaticle\Flowforge\Tests\Fixtures\Task;

// Create a testable class that exposes protected methods
class RetryMechanismTestHelper
{
    use InteractsWithBoard {
        isDuplicatePositionError as public;
    }

    public function getBoard(): \Relaticle\Flowforge\Board
    {
        throw new RuntimeException('Not implemented for testing');
    }

    public function getBoardQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return null;
    }
}

describe('isDuplicatePositionError detection', function () {
    test('detects SQLite UNIQUE constraint failure', function () {
        $helper = new RetryMechanismTestHelper;

        // SQLite constraint violation
        $exception = new QueryException(
            'sqlite',
            'INSERT INTO tasks ...',
            [],
            new PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: tasks.status, tasks.order_position')
        );

        // Set errorInfo with SQLite error code
        $reflection = new ReflectionProperty($exception, 'errorInfo');
        $reflection->setAccessible(true);
        $reflection->setValue($exception, ['23000', 19, 'UNIQUE constraint failed']);

        expect($helper->isDuplicatePositionError($exception))->toBeTrue();
    });

    test('detects MySQL ER_DUP_ENTRY error', function () {
        $helper = new RetryMechanismTestHelper;

        // MySQL duplicate entry error
        $exception = new QueryException(
            'mysql',
            'INSERT INTO tasks ...',
            [],
            new PDOException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '100.5-todo' for key 'unique_position_per_column'")
        );

        $reflection = new ReflectionProperty($exception, 'errorInfo');
        $reflection->setAccessible(true);
        $reflection->setValue($exception, ['23000', 1062, 'Duplicate entry']);

        expect($helper->isDuplicatePositionError($exception))->toBeTrue();
    });

    test('detects PostgreSQL unique_violation error', function () {
        $helper = new RetryMechanismTestHelper;

        // PostgreSQL unique violation error
        $exception = new QueryException(
            'pgsql',
            'INSERT INTO tasks ...',
            [],
            new PDOException('SQLSTATE[23505]: Unique violation: duplicate key value violates unique constraint "unique_position_per_column"')
        );

        $reflection = new ReflectionProperty($exception, 'errorInfo');
        $reflection->setAccessible(true);
        $reflection->setValue($exception, ['23505', 23505, 'duplicate key value']);

        expect($helper->isDuplicatePositionError($exception))->toBeTrue();
    });

    test('detects error by message containing unique_position_per_column', function () {
        $helper = new RetryMechanismTestHelper;

        // Generic error with constraint name in message
        $exception = new QueryException(
            'sqlite',
            'INSERT INTO tasks ...',
            [],
            new PDOException('UNIQUE constraint failed: unique_position_per_column')
        );

        $reflection = new ReflectionProperty($exception, 'errorInfo');
        $reflection->setAccessible(true);
        $reflection->setValue($exception, ['23000', 999, 'unknown error']); // Unknown error code

        expect($helper->isDuplicatePositionError($exception))->toBeTrue();
    });

    test('returns false for non-duplicate errors', function () {
        $helper = new RetryMechanismTestHelper;

        // Foreign key constraint error (not duplicate)
        $exception = new QueryException(
            'mysql',
            'INSERT INTO tasks ...',
            [],
            new PDOException('SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails')
        );

        $reflection = new ReflectionProperty($exception, 'errorInfo');
        $reflection->setAccessible(true);
        $reflection->setValue($exception, ['23000', 1452, 'foreign key constraint fails']);

        expect($helper->isDuplicatePositionError($exception))->toBeFalse();
    });
});

describe('database unique constraint behavior', function () {
    beforeEach(function () {
        Task::create(['title' => 'Existing', 'status' => 'todo', 'order_position' => '1500.0000000000']);
    });

    test('unique constraint throws QueryException on duplicate', function () {
        expect(fn () => Task::create([
            'title' => 'Duplicate',
            'status' => 'todo',
            'order_position' => '1500.0000000000',
        ]))->toThrow(QueryException::class);
    });

    test('same position in different columns is allowed', function () {
        // Same position, different status (column)
        $task = Task::create([
            'title' => 'Different Column',
            'status' => 'in_progress', // Different column
            'order_position' => '1500.0000000000', // Same position
        ]);

        expect($task->exists)->toBeTrue();
    });

    test('null positions are allowed', function () {
        // NULL is special in unique constraints - multiple NULLs are allowed
        Task::create(['title' => 'Null 1', 'status' => 'todo', 'order_position' => null]);
        $task2 = Task::create(['title' => 'Null 2', 'status' => 'todo', 'order_position' => null]);

        expect($task2->exists)->toBeTrue();
    });
});
