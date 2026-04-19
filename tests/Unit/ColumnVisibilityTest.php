<?php

declare(strict_types=1);

use Relaticle\Flowforge\Column;

describe('Column visibility', function () {
    test('column is visible by default', function () {
        $column = Column::make('todo');

        expect($column->isVisible())->toBeTrue()
            ->and($column->isHidden())->toBeFalse();
    });

    test('hidden(true) boolean literal hides the column', function () {
        $column = Column::make('todo')->hidden(true);

        expect($column->isHidden())->toBeTrue()
            ->and($column->isVisible())->toBeFalse();
    });

    test('hidden(false) boolean literal keeps the column visible', function () {
        $column = Column::make('todo')->hidden(false);

        expect($column->isVisible())->toBeTrue()
            ->and($column->isHidden())->toBeFalse();
    });

    test('visible(false) boolean literal hides the column', function () {
        $column = Column::make('todo')->visible(false);

        expect($column->isHidden())->toBeTrue()
            ->and($column->isVisible())->toBeFalse();
    });

    test('hidden closure is evaluated on every check', function () {
        $flag = true;
        $column = Column::make('todo')->hidden(function () use (&$flag) {
            return $flag;
        });

        expect($column->isHidden())->toBeTrue();

        $flag = false;

        expect($column->isHidden())->toBeFalse();
    });

    test('visible closure is evaluated on every check', function () {
        $flag = false;
        $column = Column::make('todo')->visible(function () use (&$flag) {
            return $flag;
        });

        expect($column->isVisible())->toBeFalse();

        $flag = true;

        expect($column->isVisible())->toBeTrue();
    });

    test('hidden(true) takes precedence over visible(true)', function () {
        $column = Column::make('todo')
            ->hidden(true)
            ->visible(true);

        expect($column->isHidden())->toBeTrue()
            ->and($column->isVisible())->toBeFalse();
    });

    test('visible(false) hides even when hidden(false) is also set', function () {
        $column = Column::make('todo')
            ->hidden(false)
            ->visible(false);

        expect($column->isHidden())->toBeTrue()
            ->and($column->isVisible())->toBeFalse();
    });
});
