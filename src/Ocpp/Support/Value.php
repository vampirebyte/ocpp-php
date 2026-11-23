<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\Support;

/**
 * Reads values from raw OCPP message arrays, whose elements can be of any type.
 *
 * @internal
 */
final class Value
{
    /**
     * A scalar as a string, the way PHP coerces it, or null for anything else.
     */
    public static function toString(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * A received value as text for an error message, e.g. "7", "null" or "array".
     */
    public static function describe(mixed $value): string
    {
        return self::toString($value) ?? get_debug_type($value);
    }
}
