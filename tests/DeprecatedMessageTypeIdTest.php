<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SolutionForest\OcppPhp\Ocpp\Messages\MessageTypeId;

/**
 * The per-version MessageTypeId enums are deprecated aliases of the shared enum.
 *
 * They exist only at runtime (class_alias), so they are referenced by name here.
 */
final class DeprecatedMessageTypeIdTest extends TestCase
{
    private const string V16 = 'SolutionForest\OcppPhp\Ocpp\v16\Enums\MessageTypeId';

    private const string V201 = 'SolutionForest\OcppPhp\Ocpp\v201\Enums\MessageTypeId';

    public function test_old_names_are_the_shared_enum(): void
    {
        self::assertSame(MessageTypeId::Call, constant(self::V16.'::Call'));
        self::assertSame(MessageTypeId::CallError, constant(self::V201.'::CallError'));
        self::assertTrue(is_a(MessageTypeId::Send, self::V16));
    }

    public function test_alias_files_can_be_loaded_after_the_shared_enum(): void
    {
        // The autoloader includes them only when an old name is used before the shared enum
        // is loaded; including them afterwards must not redeclare anything.
        require __DIR__.'/../src/Ocpp/v16/Enums/MessageTypeId.php';
        require __DIR__.'/../src/Ocpp/v201/Enums/MessageTypeId.php';

        self::assertTrue(enum_exists(self::V16));
        self::assertTrue(enum_exists(self::V201));
    }
}
