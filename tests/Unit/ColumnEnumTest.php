<?php

declare(strict_types=1);

use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Tests\Fixtures\IntBackedEnum;
use Relaticle\Flowforge\Tests\Fixtures\LabelOnlyEnum;
use Relaticle\Flowforge\Tests\Fixtures\PlainEnum;
use Relaticle\Flowforge\Tests\Fixtures\StatusEnum;

describe('Column::enum()', function () {
    test('builds a column from an enum implementing HasLabel, HasColor, and HasIcon', function () {
        $column = Column::enum(StatusEnum::Active);

        expect($column->getName())->toBe('active')
            ->and($column->getLabel())->toBe('Active Item')
            ->and($column->getColor())->toBe('success')
            ->and($column->getIcon())->toBe('heroicon-o-check');
    });

    test('applies only the interfaces the enum implements', function () {
        $column = Column::enum(LabelOnlyEnum::Draft);

        expect($column->getName())->toBe('draft')
            ->and($column->getLabel())->toBe('Draft Item')
            ->and($column->getColor())->toBeNull()
            ->and($column->getIcon())->toBeNull();
    });

    test('falls back to generated defaults for an enum without any Filament contracts', function () {
        $column = Column::enum(PlainEnum::Archived);

        expect($column->getName())->toBe('archived')
            ->and($column->getLabel())->toBe('Archived')
            ->and($column->getColor())->toBeNull()
            ->and($column->getIcon())->toBeNull();
    });

    test('casts int-backed enum values to string for the column identifier', function () {
        $column = Column::enum(IntBackedEnum::First);

        expect($column->getName())->toBe('1')
            ->and($column->getName())->toBeString();
    });
});
