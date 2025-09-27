<?php

declare(strict_types=1);

use Filament\Support\Colors\Color;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Support\ColorResolver;

describe('Column Color Handling', function () {
    it('can set semantic colors on columns', function () {
        $column = Column::make('test')
            ->color('primary');

        expect($column->getColor())->toBe('primary');
    });

    it('can set tailwind colors on columns by name', function () {
        $colors = ['red', 'blue', 'green', 'amber', 'purple', 'pink', 'gray'];

        foreach ($colors as $color) {
            $column = Column::make('test')->color($color);
            expect($column->getColor())->toBe($color);
        }
    });

    it('can set Color constants directly', function () {
        $column = Column::make('test')
            ->color(Color::Red);

        $color = $column->getColor();
        expect($color)->toBeArray()
            ->and($color)->toHaveKey(50)
            ->and($color)->toHaveKey(500)
            ->and($color)->toHaveKey(900);
    });

    it('can set hex colors on columns', function () {
        $column = Column::make('test')
            ->color('#ff0000');

        expect($column->getColor())->toBe('#ff0000');
    });

    it('can set default color on columns', function () {
        $column = Column::make('test')
            ->defaultColor('gray');

        expect($column->getColor())->toBe('gray');
    });

    it('uses default color when no color is set', function () {
        $column = Column::make('test')
            ->defaultColor('info');

        expect($column->getColor())->toBe('info');
    });

    it('prefers explicit color over default color', function () {
        $column = Column::make('test')
            ->defaultColor('gray')
            ->color('primary');

        expect($column->getColor())->toBe('primary');
    });
});

describe('ColorResolver', function () {
    it('resolves semantic colors', function () {
        expect(ColorResolver::resolve('primary'))->toBe('primary')
            ->and(ColorResolver::resolve('danger'))->toBe('danger')
            ->and(ColorResolver::resolve('success'))->toBe('success')
            ->and(ColorResolver::isSemantic('primary'))->toBeTrue()
            ->and(ColorResolver::isSemantic('danger'))->toBeTrue();
    });

    it('resolves Tailwind color names', function () {
        $redColor = ColorResolver::resolve('red');
        expect($redColor)->toBeArray()
            ->and($redColor)->toHaveKey(500)
            ->and($redColor[500])->toContain('oklch');

        $blueColor = ColorResolver::resolve('blue');
        expect($blueColor)->toBeArray()
            ->and($blueColor)->toHaveKey(500);
    });

    it('resolves Color constants', function () {
        $color = ColorResolver::resolve(Color::Green);
        expect($color)->toBeArray()
            ->and($color)->toBe(Color::Green)
            ->and($color)->toHaveKey(500);
    });

    it('resolves hex colors', function () {
        $color = ColorResolver::resolve('#ff0000');
        expect($color)->toBeArray()
            ->and($color)->toHaveKey(500);
    });

    it('handles invalid colors gracefully', function () {
        expect(ColorResolver::resolve('invalid-color'))->toBeNull()
            ->and(ColorResolver::resolve('not-a-color'))->toBeNull()
            ->and(ColorResolver::resolve('#gggggg'))->toBeNull()
            ->and(ColorResolver::resolve(''))->toBeNull()
            ->and(ColorResolver::resolve(null))->toBeNull();
    });

    it('is case-insensitive for Tailwind colors', function () {
        expect(ColorResolver::resolve('RED'))->toBeArray()
            ->and(ColorResolver::resolve('Red'))->toBeArray()
            ->and(ColorResolver::resolve('red'))->toBeArray();
    });

    it('correctly identifies semantic vs non-semantic colors', function () {
        expect(ColorResolver::isSemantic('primary'))->toBeTrue()
            ->and(ColorResolver::isSemantic(Color::Red))->toBeFalse()
            ->and(ColorResolver::isSemantic('#ff0000'))->toBeFalse()
            ->and(ColorResolver::isSemantic('red'))->toBeFalse();
    });
});
