<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use SolutionForest\OcppPhp\Ocpp\Exceptions\InternalError;
use SolutionForest\OcppPhp\Ocpp\Messages\Call;
use SolutionForest\OcppPhp\Ocpp\Messages\CallError;
use SolutionForest\OcppPhp\Ocpp\Messages\CallResult;
use SolutionForest\OcppPhp\Ocpp\v16\CallResults\Heartbeat as HeartbeatResult;
use SolutionForest\OcppPhp\Ocpp\v16\Calls\Heartbeat;

/**
 * Call::fromArray(), CallResult::fromArray() and CallError::fromArray() with malformed input
 * from the network: each problem is reported with an Exception that names it.
 */
final class MessageFactoryTest extends TestCase
{
    public function test_call_rejects_other_message_types(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Invalid message type for Call, expected 2 or 6 got 3');

        Call::fromArray([3, 'id', 'Heartbeat', []], 'v1.6');
    }

    public function test_call_rejects_an_unknown_action(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Unknown call action: Bogus');

        Call::fromArray([2, 'id', 'Bogus', []], 'v1.6');
    }

    public function test_call_on_a_message_class_only_accepts_that_action(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Unknown call action: BootNotification');

        Heartbeat::fromArray([2, 'id', 'BootNotification', ['chargePointModel' => 'M', 'chargePointVendor' => 'V']], 'v1.6');
    }

    public function test_call_rejects_a_unique_id_that_is_not_a_string(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Invalid unique id for Heartbeat: array');

        Call::fromArray([2, ['id'], 'Heartbeat', []], 'v1.6');
    }

    public function test_call_accepts_a_numeric_unique_id_as_a_string(): void
    {
        $call = Call::fromArray([2, 123, 'Heartbeat', []], 'v1.6');

        self::assertSame('123', $call->getUniqueId());
    }

    public function test_call_rejects_a_payload_that_is_not_an_object(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Invalid payload for Heartbeat, expected an object got x');

        Call::fromArray([2, 'id', 'Heartbeat', 'x'], 'v1.6');
    }

    public function test_call_without_payload_has_an_empty_payload(): void
    {
        $call = Call::fromArray([2, 'id', 'Heartbeat'], 'v1.6');

        self::assertSame('[2,"id","Heartbeat",{}]', json_encode($call->toArray()));
    }

    public function test_new_calls_get_a_uuid_when_ocpp_message_uuid_is_set(): void
    {
        putenv('OCPP_MESSAGE_UUID=1');

        try {
            $call = new Heartbeat();
        } finally {
            putenv('OCPP_MESSAGE_UUID');
        }

        self::assertTrue(Uuid::isValid($call->getUniqueId()));
    }

    public function test_new_calls_get_a_numeric_unique_id_by_default(): void
    {
        self::assertMatchesRegularExpression('/^\d+$/', new Heartbeat()->getUniqueId());
    }

    public function test_call_result_rejects_other_message_types(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Invalid message type for CallResult, expected 3 got null');

        CallResult::fromArray([], 'Heartbeat', 'v1.6');
    }

    public function test_call_result_rejects_an_unknown_action(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Unknown call result action: Bogus');

        CallResult::fromArray([3, 'id', []], 'Bogus', 'v1.6');
    }

    public function test_call_result_rejects_a_missing_unique_id(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Invalid unique id for Heartbeat: null');

        CallResult::fromArray([3], 'Heartbeat', 'v1.6');
    }

    public function test_call_result_rejects_a_payload_that_is_not_an_object(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Invalid payload for Heartbeat, expected an object got 1');

        CallResult::fromArray([3, 'id', true], 'Heartbeat', 'v1.6');
    }

    public function test_call_result_from_array(): void
    {
        $result = HeartbeatResult::fromArray([3, 'id', ['currentTime' => '2026-01-01T00:00:00Z']], 'Heartbeat', 'v1.6');

        self::assertSame('2026-01-01T00:00:00Z', $result->currentTime);
        self::assertSame('id', $result->getUniqueId());
    }

    public function test_call_error_rejects_a_missing_unique_id(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Invalid unique id for CallError: null');

        CallError::fromArray([4, null, 'InternalError', '', []]);
    }

    public function test_call_error_on_an_error_class_only_accepts_its_error_code(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Error code NotImplemented is not a '.InternalError::class);

        InternalError::fromArray([4, 'id', 'NotImplemented', '', []]);
    }

    public function test_call_error_on_an_error_class_with_its_error_code(): void
    {
        $error = InternalError::fromArray([4, 'id', 'InternalError', 'Oops', ['detail']]);

        self::assertSame(['detail'], $error->errorDetails);
    }

    public function test_call_error_can_be_built_with_a_custom_code_and_description(): void
    {
        $error = new InternalError('id', 'Custom', 'Custom description', ['detail']);

        self::assertSame([4, 'id', 'Custom', 'Custom description', ['detail']], [
            $error->messageTypeID,
            $error->getUniqueId(),
            $error->errorCode,
            $error->errorDescription,
            $error->errorDetails,
        ]);
    }
}
