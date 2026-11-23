<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SolutionForest\OcppPhp\Ocpp\Datatype;
use SolutionForest\OcppPhp\Ocpp\v201\Calls\Heartbeat;

/**
 * A Datatype as a library user may write it: a property without a native type.
 */
final class UntypedPropertyDatatype extends Datatype
{
    /**
     * @var mixed
     */
    public $anything;
}

/**
 * How decoded JSON is assigned to message properties (HydratesProperties).
 */
final class HydrationTest extends TestCase
{
    public function test_unknown_properties_are_ignored(): void
    {
        $call = new Heartbeat(['bogus' => 1]);

        self::assertSame('{}', json_encode($call->getPayload()));
    }

    public function test_a_json_array_instead_of_an_object_sets_nothing(): void
    {
        $call = new Heartbeat(['first', 'second']);

        self::assertSame('{}', json_encode($call->getPayload()));
    }

    public function test_free_form_objects_become_std_class(): void
    {
        $call = new Heartbeat(['customData' => ['vendorId' => 'acme', 'level' => 3]]);

        self::assertInstanceOf(stdClass::class, $call->customData);
        self::assertSame('{"customData":{"vendorId":"acme","level":3}}', json_encode($call->getPayload()));
    }

    public function test_arrays_for_untyped_properties_are_kept_as_arrays(): void
    {
        $datatype = new UntypedPropertyDatatype(['anything' => ['a' => 1]]);

        self::assertSame(['a' => 1], $datatype->anything);
    }
}
