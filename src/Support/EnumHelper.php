<?php

namespace Relaticle\Flowforge\Support;

class EnumHelper {
    public static function convertEnumToString($value): string
    {
        if (is_object($value)) {
            // Handle Laravel Enums (implements UnitEnum)
            if ($value instanceof \UnitEnum) {
                return $value->value ?? $value->name;
            }
            // Handle objects with value property
            if (property_exists($value, 'value')) {
                return (string) $value->value;
            }
            // Handle objects with __toString method
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            // Fallback: try to get class name or serialize
            return class_basename($value);
        }

        return (string) $value;
    }
}