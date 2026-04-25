<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Support;

use BackedEnum;
use InvalidArgumentException;
use UnitEnum;

final class EnumHelper
{
    public static function convertEnumToString(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_object($value)) {
            if (property_exists($value, 'value')) {
                return (string) $value->value;
            }

            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            throw new InvalidArgumentException(sprintf(
                'Cannot convert object of type %s to a column identifier string.',
                $value::class,
            ));
        }

        return (string) $value;
    }
}
