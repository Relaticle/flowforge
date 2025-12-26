<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;

test('flowforgePositionColumn macro creates decimal position column', function () {
    $blueprint = new Blueprint('test_table');
    $column = $blueprint->flowforgePositionColumn();

    expect($column)->toBeInstanceOf(ColumnDefinition::class)
        ->and($column->get('type'))->toBe('decimal')
        ->and($column->get('nullable'))->toBeTrue()
        ->and($column->get('total'))->toBe(20)
        ->and($column->get('places'))->toBe(10);
});

test('flowforgePositionColumn macro accepts custom column name', function () {
    $blueprint = new Blueprint('test_table');
    $column = $blueprint->flowforgePositionColumn('sort_order');

    expect($column)->toBeInstanceOf(ColumnDefinition::class)
        ->and($column->get('name'))->toBe('sort_order')
        ->and($column->get('type'))->toBe('decimal')
        ->and($column->get('nullable'))->toBeTrue();
});

test('flowforgePositionColumn macro creates column with correct precision for 33+ bisections', function () {
    $blueprint = new Blueprint('test_table');
    $column = $blueprint->flowforgePositionColumn();

    // DECIMAL(20,10) = 10 integer digits + 10 decimal places
    // This supports approximately 33 bisections before hitting MIN_GAP
    expect($column->get('total'))->toBe(20)
        ->and($column->get('places'))->toBe(10);
});
