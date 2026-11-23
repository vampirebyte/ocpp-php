<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SolutionForest\OcppPhp\Ocpp\Exceptions\FormatViolationError;
use SolutionForest\OcppPhp\Ocpp\Exceptions\ProtocolError;
use SolutionForest\OcppPhp\Ocpp\Exceptions\TypeConstraintViolationError;
use SolutionForest\OcppPhp\Ocpp\Exceptions\ValidationError;
use SolutionForest\OcppPhp\Ocpp\JsonSchemaValidator;
use SolutionForest\OcppPhp\Ocpp\Messages\CallError;

/**
 * The CallError returned for each kind of schema violation.
 */
final class ValidationErrorsTest extends TestCase
{
    public function test_a_wrong_type_is_a_type_constraint_violation(): void
    {
        $error = $this->validateStatusNotification(['connectorId' => 'one', 'errorCode' => 'NoError', 'status' => 'Available']);

        self::assertInstanceOf(TypeConstraintViolationError::class, $error);
        self::assertSame('u1', $error->getUniqueId());
    }

    public function test_an_unknown_property_is_a_format_violation(): void
    {
        $error = $this->validateStatusNotification(['connectorId' => 1, 'errorCode' => 'NoError', 'status' => 'Available', 'bogus' => true]);

        self::assertInstanceOf(FormatViolationError::class, $error);
        self::assertSame(['The property bogus is not defined and the definition does not allow additional properties'], $error->errorDetails);
    }

    public function test_an_unknown_nested_property_is_a_format_violation(): void
    {
        $error = JsonSchemaValidator::validate(
            ['messageTypeID' => 2, 'uniqueId' => 'u1', 'action' => 'BootNotification', 'payload' => [
                'reason' => 'PowerUp',
                'chargingStation' => ['model' => 'M', 'vendorName' => 'V', 'bogus' => true],
            ]],
            'v2.1',
            false,
        );

        self::assertInstanceOf(FormatViolationError::class, $error);
        self::assertSame(['The property bogus is not defined and the definition does not allow additional properties'], $error->errorDetails);
    }

    public function test_other_violations_name_the_property(): void
    {
        $error = $this->validateStatusNotification(['connectorId' => 1, 'errorCode' => 'NoError', 'status' => 'Bogus']);

        self::assertInstanceOf(FormatViolationError::class, $error);
        self::assertIsString($error->errorDetails[0] ?? null);
        self::assertStringStartsWith('Payload status is not valid: ', $error->errorDetails[0]);
    }

    public function test_a_call_error_without_error_code_is_a_protocol_error(): void
    {
        // ValidationError has no default error code; validating it used to throw an Error.
        $error = JsonSchemaValidator::validate(new ValidationError('id'), 'v1.6', false);

        self::assertInstanceOf(ProtocolError::class, $error);
        self::assertSame(['The property errorCode is required and must be a non-empty string'], $error->errorDetails);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validateStatusNotification(array $payload): CallError
    {
        $result = JsonSchemaValidator::validate(
            ['messageTypeID' => 2, 'uniqueId' => 'u1', 'action' => 'StatusNotification', 'payload' => $payload],
            'v1.6',
            false,
        );

        self::assertInstanceOf(CallError::class, $result);

        return $result;
    }
}
