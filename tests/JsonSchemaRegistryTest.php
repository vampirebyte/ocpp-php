<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SolutionForest\OcppPhp\Ocpp\JsonSchemaRegistry;
use SolutionForest\OcppPhp\Ocpp\v201\Calls\Heartbeat;
use SolutionForest\OcppPhp\Ocpp\v21\CallResults\Heartbeat as HeartbeatResult;
use SolutionForest\OcppPhp\Ocpp\v21\Calls\BootNotification;
use SolutionForest\OcppPhp\Ocpp\v21\Datatypes\ChargingStationType;

final class JsonSchemaRegistryTest extends TestCase
{
    public function test_a_call_result_needs_its_action(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Action parameter is required for CallResult messages');

        new JsonSchemaRegistry()->createFromArray([3, 'id', []], 'v2.1');
    }

    public function test_schema_of_a_parsed_message_with_an_invalid_type(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Unvalid message type for validation: 4');

        new JsonSchemaRegistry()->getSchema(['messageTypeID' => 4, 'action' => 'Heartbeat'], 'v2.1');
    }

    public function test_schema_of_an_unknown_action(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Schema not found: BogusRequest.json in version v2.1');

        new JsonSchemaRegistry()->getSchema(['messageTypeID' => 2, 'action' => 'Bogus'], 'v2.1');
    }

    public function test_schema_of_a_message_without_action_is_not_found(): void
    {
        // Used to recurse until PHP ran out of memory.
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Schema not found: Request.json in version v2.1');

        new JsonSchemaRegistry()->getSchema(['messageTypeID' => 2, 'action' => '', 'payload' => []], 'v2.1');
    }

    public function test_schema_suffixes_are_not_added_twice(): void
    {
        $registry = new JsonSchemaRegistry();

        self::assertSame(
            $registry->loadSchema('Heartbeat.json', 'Calls', 'v2.1'),
            $registry->loadSchema('HeartbeatRequest.json', 'Calls', 'v2.1'),
        );
    }

    public function test_latest_version_is_used_by_default(): void
    {
        $registry = new JsonSchemaRegistry();

        self::assertSame('v2.1', $registry->findLatestVersion());
        self::assertSame('urn:OCPP:Cp:2:2025:1:HeartbeatRequest', $registry->loadSchema('Heartbeat.json', 'Calls')['$id'] ?? null);
    }

    public function test_a_subclass_may_return_no_schema(): void
    {
        $registry = new class extends JsonSchemaRegistry
        {
            public function loadSchema(string $filename, string $type, string $version = 'latest'): ?array
            {
                return null;
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Schema not found: Heartbeat.json in version v2.1');

        $registry->getSchema(new HeartbeatResult('id', ['currentTime' => '2026-01-01T00:00:00Z']), 'v2.1');
    }

    public function test_deprecated_create_call_from_array_hydrates_like_create_from_array(): void
    {
        $call = new JsonSchemaRegistry()->createCallFromArray(
            [2, 'id', 'BootNotification', ['reason' => 'PowerUp', 'chargingStation' => ['model' => 'M', 'vendorName' => 'V']]],
            'v2.1',
        );

        self::assertInstanceOf(BootNotification::class, $call);
        self::assertInstanceOf(ChargingStationType::class, $call->chargingStation);
        self::assertSame('id', $call->getUniqueId());
    }

    public function test_deprecated_create_call_from_array_only_accepts_calls(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Invalid message type for Call');

        new JsonSchemaRegistry()->createCallFromArray([6, 'id', 'NotifyPeriodicEventStream', []], 'v2.1');
    }

    public function test_deprecated_create_call_result_from_array(): void
    {
        $result = new JsonSchemaRegistry()->createCallResultFromArray([3, 'id', ['currentTime' => '2026-01-01T00:00:00Z']], 'Heartbeat', 'v2.1');

        self::assertInstanceOf(HeartbeatResult::class, $result);
        self::assertSame('2026-01-01T00:00:00Z', $result->currentTime);
    }

    public function test_deprecated_create_call_result_from_array_only_accepts_call_results(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIs('Invalid message type for CallResult');

        new JsonSchemaRegistry()->createCallResultFromArray([2, 'id', 'Heartbeat', []], 'Heartbeat', 'v2.1');
    }

    public function test_schema_of_a_message_object(): void
    {
        $schema = new JsonSchemaRegistry()->getSchema(new Heartbeat(), 'v2.0.1');

        self::assertSame('OCPP 2.0.1 FINAL', $schema['comment'] ?? null);
    }
}
