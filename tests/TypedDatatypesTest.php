<?php

declare(strict_types=1);

use SolutionForest\OcppPhp\Ocpp\Messages\CallResult;
use SolutionForest\OcppPhp\Ocpp\v16\CallResults\Authorize;
use SolutionForest\OcppPhp\Ocpp\v16\CallResults\GetCompositeSchedule as V16GetCompositeSchedule;
use SolutionForest\OcppPhp\Ocpp\v16\Datatypes\ChargingSchedule;
use SolutionForest\OcppPhp\Ocpp\v16\Datatypes\ChargingSchedulePeriod;
use SolutionForest\OcppPhp\Ocpp\v16\Datatypes\IdTagInfo;
use SolutionForest\OcppPhp\Ocpp\v201\CallResults\GetCompositeSchedule as V201GetCompositeSchedule;
use SolutionForest\OcppPhp\Ocpp\v201\Datatypes\ChargingSchedulePeriodType;
use SolutionForest\OcppPhp\Ocpp\v201\Datatypes\CompositeScheduleType;
use SolutionForest\OcppPhp\Ocpp\v21\Calls\BootNotification;
use SolutionForest\OcppPhp\Ocpp\v21\Datatypes\ChargingStationType;

require_once __DIR__.'/Support/JsonSchemaExampleFactory.php';

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolutionForest\OcppPhp\Ocpp\Datatype;
use SolutionForest\OcppPhp\Ocpp\JsonSchemaRegistry;
use SolutionForest\OcppPhp\Ocpp\JsonSchemaValidator;
use SolutionForest\OcppPhp\Ocpp\Messages\Message;

/**
 * Nested JSON objects are hydrated into the generated Datatype classes at every depth,
 * and serialize back to exactly the JSON that came in.
 */
final class TypedDatatypesTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string, int}>
     */
    public static function provideMessageSchemas(): iterable
    {
        foreach (['v16' => 'v1.6', 'v201' => 'v2.0.1', 'v21' => 'v2.1'] as $dir => $version) {
            foreach (glob(dirname(__DIR__)."/src/Ocpp/{$dir}/Schemas/*.json") ?: [] as $schemaFile) {
                $name = basename($schemaFile, '.json');
                $isResponse = str_ends_with($name, 'Response');
                $action = preg_replace('/(Request|Response)$/', '', $name) ?? $name;
                $type = $isResponse ? 3 : (($dir !== 'v16' && $action === $name) ? 6 : 2);

                yield "{$dir}/{$name}" => [$schemaFile, $version, $action, $type];
            }
        }
    }

    public function test_v16_nested_objects_are_typed_at_every_depth(): void
    {
        $result = $this->receiveResult(V16GetCompositeSchedule::class, 'v1.6', 'GetCompositeSchedule', [
            'status' => 'Accepted',
            'connectorId' => 1,
            'chargingSchedule' => [
                'chargingRateUnit' => 'A',
                'chargingSchedulePeriod' => [['startPeriod' => 0, 'limit' => 16.5]],
            ],
        ]);

        self::assertInstanceOf(ChargingSchedule::class, $result->chargingSchedule);
        self::assertInstanceOf(ChargingSchedulePeriod::class, $result->chargingSchedule->chargingSchedulePeriod[0]);
        self::assertSame(16.5, $result->chargingSchedule->chargingSchedulePeriod[0]->limit);
        self::assertNull($result->chargingSchedule->duration);
    }

    public function test_v16_id_tag_info_is_typed(): void
    {
        $result = $this->receiveResult(Authorize::class, 'v1.6', 'Authorize', ['idTagInfo' => ['status' => 'Accepted']]);

        self::assertInstanceOf(IdTagInfo::class, $result->idTagInfo);
        self::assertSame('Accepted', $result->idTagInfo->status);
        self::assertStringContainsString(
            'AuthorizationStatus',
            (string) new ReflectionProperty($result->idTagInfo, 'status')->getType(),
        );
    }

    public function test_v201_nested_objects_use_the_generated_datatypes(): void
    {
        $result = $this->receiveResult(V201GetCompositeSchedule::class, 'v2.0.1', 'GetCompositeSchedule', [
            'status' => 'Accepted',
            'schedule' => [
                'evseId' => 1,
                'duration' => 60,
                'scheduleStart' => '2026-01-01T00:00:00Z',
                'chargingRateUnit' => 'A',
                'chargingSchedulePeriod' => [['startPeriod' => 0, 'limit' => 16.5]],
            ],
        ]);

        self::assertInstanceOf(CompositeScheduleType::class, $result->schedule);
        self::assertInstanceOf(ChargingSchedulePeriodType::class, $result->schedule->chargingSchedulePeriod[0]);
    }

    public function test_messages_can_be_built_from_datatype_objects(): void
    {
        $call = new BootNotification([
            'reason' => 'PowerUp',
            'chargingStation' => new ChargingStationType([
                'model' => 'M1',
                'vendorName' => 'Acme',
            ]),
        ]);

        self::assertSame(
            '{"chargingStation":{"model":"M1","vendorName":"Acme"},"reason":"PowerUp"}',
            json_encode($call->getPayload()),
        );
        self::assertTrue(JsonSchemaValidator::validate($call, 'v2.1'));
    }

    public function test_only_free_form_objects_stay_untyped(): void
    {
        $untyped = [];

        foreach (['v16', 'v201', 'v21'] as $version) {
            foreach (['Calls', 'CallResults', 'Datatypes'] as $kind) {
                foreach (glob(dirname(__DIR__)."/src/Ocpp/{$version}/{$kind}/*.php") ?: [] as $file) {
                    /** @var class-string $class */
                    $class = "SolutionForest\\OcppPhp\\Ocpp\\{$version}\\{$kind}\\".basename($file, '.php');
                    foreach (new ReflectionClass($class)->getProperties() as $property) {
                        $type = ltrim((string) $property->getType(), '?');
                        if ($type === 'object' && $property->getName() !== 'customData') {
                            $untyped[] = "{$class}::\${$property->getName()}";
                        }
                    }
                }
            }
        }

        self::assertSame([], $untyped, 'Object properties should be typed with a Datatype class');
    }

    #[DataProvider('provideMessageSchemas')]
    public function test_every_message_round_trips_through_its_classes(string $schemaFile, string $version, string $action, int $type): void
    {
        $schema = json_decode((string) file_get_contents($schemaFile), true);
        self::assertIsArray($schema);
        $payload = JsonSchemaExampleFactory::createPayload($schema);
        $registry = new JsonSchemaRegistry();

        $message = $type === 3
            ? $registry->createFromArray([3, 'id', $payload], $version, $action)
            : $registry->createFromArray([$type, 'id', $action, $payload], $version);

        self::assertInstanceOf(Message::class, $message);
        self::assertTrue(JsonSchemaValidator::validate($message, $version));
        self::assertEquals(
            json_decode((string) json_encode($payload), true),
            json_decode((string) json_encode($message->getPayload()), true),
        );
    }

    public function test_datatypes_extend_the_shared_base(): void
    {
        $datatypes = glob(__DIR__.'/../src/Ocpp/*/Datatypes/*.php') ?: [];
        self::assertNotEmpty($datatypes);

        foreach ($datatypes as $file) {
            $class = 'SolutionForest\\OcppPhp\\Ocpp\\'.basename(dirname($file, 2)).'\\Datatypes\\'.basename($file, '.php');
            self::assertTrue(is_subclass_of($class, Datatype::class), "{$class} should extend Datatype");
        }
    }

    /**
     * Parse a CallResult as it arrives from the network: JSON-encoded, then decoded to arrays.
     *
     * @template T of CallResult
     *
     * @param  class-string<T>  $class
     * @param  array<string, mixed>  $payload
     * @return T
     */
    private function receiveResult(string $class, string $version, string $action, array $payload): CallResult
    {
        $message = json_decode((string) json_encode([3, 'id', $payload]), true);
        self::assertIsArray($message);

        $result = new JsonSchemaRegistry()->createFromArray($message, $version, $action);
        self::assertInstanceOf($class, $result);

        return $result;
    }
}
