<?php

declare(strict_types=1);

use Relaticle\Flowforge\Support\EnumHelper;
use Relaticle\Flowforge\Tests\Fixtures\StatusEnum;

describe('EnumHelper::convertEnumToString()', function () {
    test('returns string value of a backed enum', function () {
        expect(EnumHelper::convertEnumToString(StatusEnum::ToDo))->toBe('todo')
            ->and(EnumHelper::convertEnumToString(StatusEnum::InProgress))->toBe('in_progress');
    });

    test('returns string unchanged', function () {
        expect(EnumHelper::convertEnumToString('todo'))->toBe('todo')
            ->and(EnumHelper::convertEnumToString('anything'))->toBe('anything');
    });

    test('casts integer to string', function () {
        expect(EnumHelper::convertEnumToString(42))->toBe('42');
    });

    test('throws for unknown objects', function () {
        $obj = new class {};

        EnumHelper::convertEnumToString($obj);
    })->throws(InvalidArgumentException::class);

    test('returns value from object with value property', function () {
        $obj = new class
        {
            public string $value = 'from_value_property';
        };

        expect(EnumHelper::convertEnumToString($obj))->toBe('from_value_property');
    });

    test('returns result of __toString for stringable objects', function () {
        $obj = new class
        {
            public function __toString(): string
            {
                return 'stringable_result';
            }
        };

        expect(EnumHelper::convertEnumToString($obj))->toBe('stringable_result');
    });
});
