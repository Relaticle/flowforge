<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\DB;

test('flowforgePositionColumn macro creates position column with correct collation for MySQL', function () {
    // Mock MySQL connection
    DB::shouldReceive('connection->getDriverName')
        ->once()
        ->andReturn('mysql');

    $blueprint = new Blueprint('test_table');
    $column = $blueprint->flowforgePositionColumn();

    expect($column)->toBeInstanceOf(ColumnDefinition::class)
        ->and($column->get('type'))->toBe('string')
        ->and($column->get('nullable'))->toBeTrue()
        ->and($column->get('collation'))->toBe('utf8mb4_bin');
});

test('flowforgePositionColumn macro creates position column with correct collation for PostgreSQL', function () {
    // Mock PostgreSQL connection
    DB::shouldReceive('connection->getDriverName')
        ->once()
        ->andReturn('pgsql');

    $blueprint = new Blueprint('test_table');
    $column = $blueprint->flowforgePositionColumn();

    expect($column)->toBeInstanceOf(ColumnDefinition::class)
        ->and($column->get('type'))->toBe('string')
        ->and($column->get('nullable'))->toBeTrue()
        ->and($column->get('collation'))->toBe('C');
});

test('flowforgePositionColumn macro accepts custom column name', function () {
    // Mock MySQL connection
    DB::shouldReceive('connection->getDriverName')
        ->once()
        ->andReturn('mysql');

    $blueprint = new Blueprint('test_table');
    $column = $blueprint->flowforgePositionColumn('sort_order');

    expect($column)->toBeInstanceOf(ColumnDefinition::class)
        ->and($column->get('name'))->toBe('sort_order')
        ->and($column->get('collation'))->toBe('utf8mb4_bin');
});

test('flowforgePositionColumn macro works with unsupported database driver', function () {
    // Mock unsupported driver
    DB::shouldReceive('connection->getDriverName')
        ->once()
        ->andReturn('sqlite');

    $blueprint = new Blueprint('test_table');
    $column = $blueprint->flowforgePositionColumn();

    expect($column)->toBeInstanceOf(ColumnDefinition::class)
        ->and($column->get('type'))->toBe('string')
        ->and($column->get('nullable'))->toBeTrue()
        ->and($column->get('collation'))->toBeNull();
});
