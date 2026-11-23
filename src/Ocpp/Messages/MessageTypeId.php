<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\Messages;

/**
 * OCPP-J message type identifiers (first element of every message array).
 * Defined by the transport layer, not the schemas, so shared by all OCPP versions.
 */
enum MessageTypeId: int
{
    case Call = 2;
    case CallResult = 3;
    case CallError = 4;
    /**
     * OCPP 2.1+
     */
    case CallResultError = 5;
    /**
     * OCPP 2.1+
     */
    case Send = 6;

    /**
     * Strict lookup: only an actual int matches (the string "2" does not).
     */
    public static function tryFromInt(mixed $value): ?self
    {
        return is_int($value) ? self::tryFrom($value) : null;
    }
}

// Deprecated names of this enum (see src/Ocpp/v16/Enums and src/Ocpp/v201/Enums). They are
// registered as soon as this enum is loaded, so instanceof checks and type declarations
// using an old name also accept cases obtained through the new name.
class_alias(MessageTypeId::class, 'SolutionForest\OcppPhp\Ocpp\v16\Enums\MessageTypeId');
class_alias(MessageTypeId::class, 'SolutionForest\OcppPhp\Ocpp\v201\Enums\MessageTypeId');
