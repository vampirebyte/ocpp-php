<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp;

use BackedEnum;
use Exception;
use JsonSchema\ConstraintError;
use JsonSchema\Constraints\BaseConstraint;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSerializable;
use SolutionForest\OcppPhp\Ocpp\Exceptions\FormatViolationError;
use SolutionForest\OcppPhp\Ocpp\Exceptions\ProtocolError;
use SolutionForest\OcppPhp\Ocpp\Exceptions\TypeConstraintViolationError;
use SolutionForest\OcppPhp\Ocpp\Messages\Call;
use SolutionForest\OcppPhp\Ocpp\Messages\CallError;
use SolutionForest\OcppPhp\Ocpp\Messages\CallResult;
use SolutionForest\OcppPhp\Ocpp\Messages\MessageTypeId;
use SolutionForest\OcppPhp\Ocpp\Support\Value;

/**
 * @phpstan-type SchemaError array{property?: string, message?: string, constraint?: array{name?: string}}
 */
class JsonSchemaValidator
{
    /**
     * Validate an OCPP message payload against its JSON schema.
     *
     * @param  Call|CallResult|CallError|array<mixed>  $message  The message to validate
     * @param  string  $version  The OCPP version (e.g., 'v1.6', 'v2.0.1', 'v2.1')
     * @param  bool  $throwException  Whether to throw an exception on validation failure
     * @return bool|CallError Returns true if valid, or a CallError if invalid (when $throwException is false)
     *
     * @throws Exception
     */
    public static function validate(Call|CallResult|CallError|array $message, string $version, bool $throwException = true): bool|CallError
    {
        // CallError messages represent protocol-level error responses (e.g., NotImplemented).
        // They have no JSON schema to validate against — instead we verify they parse
        // into a well-formed CallError with a recognized error structure.
        if ($message instanceof CallError) {
            return self::validateCallErrorStructure($message, $throwException);
        }

        // Raw array CallError or (OCPP 2.1) CallResultError. Parse it first so we
        // validate its structure rather than blindly returning true.
        if (is_array($message) && self::isErrorType($message['messageTypeID'] ?? $message[0] ?? null)) {
            $callError = self::normalizeCallErrorArray($message);

            return self::validateCallErrorStructure($callError, $throwException);
        }

        $registry = new JsonSchemaRegistry();
        $schema = $registry->getSchema($message, $version);
        $payload = is_array($message) ? ($message['payload'] ?? null) : $message->getPayload();
        $payload = self::normalizePayload($payload, $schema);

        $validator = self::validatePayload($payload, $schema);

        if ($validator->isValid()) {
            return true;
        }

        // The error arrays built by justinrainbow/json-schema; only the first one is reported.
        /** @var list<SchemaError> $errors */
        $errors = $validator->getErrors();
        $callError = self::createCallErrorFromValidationError($errors[0] ?? [], self::extractUniqueId($message));

        if ($throwException) {
            throw new Exception(self::formatValidationExceptionMessage($callError));
        }

        return $callError;
    }

    /**
     * @param  array<mixed>  $schema
     */
    private static function validatePayload(mixed $payload, array $schema): Validator
    {
        if (self::containsUnsupportedSchemaId($schema)) {
            $schema = self::normalizeUnsupportedSchemaIds($schema);
        }

        $schemaId = self::getSchemaId($schema) ?? SchemaStorage::INTERNAL_PROVIDED_SCHEMA_URI;
        $schemaStorage = new SchemaStorage();
        $schemaStorage->addSchema($schemaId, BaseConstraint::arrayToObjectRecursive($schema));

        $validator = new Validator(new Factory($schemaStorage));
        $validator->validate($payload, $schemaStorage->getSchema($schemaId));

        return $validator;
    }

    /**
     * @param  Call|CallResult|array<mixed>  $message
     */
    private static function extractUniqueId(Call|CallResult|array $message): string
    {
        return is_array($message)
            ? Value::toString($message['uniqueId'] ?? $message['UniqueId'] ?? $message['messageId'] ?? $message[1] ?? null) ?? ''
            : $message->getUniqueId();
    }

    /**
     * @param  SchemaError  $error
     */
    private static function createCallErrorFromValidationError(array $error, string $uniqueId): CallError
    {
        $constraint = $error['constraint']['name'] ?? null;
        $errorDetails = self::formatValidationErrorDetails($error, $constraint);

        return match ($constraint) {
            ConstraintError::TYPE, ConstraintError::LENGTH_MAX => new TypeConstraintViolationError($uniqueId, null, null, [$errorDetails]),
            ConstraintError::REQUIRED => new ProtocolError($uniqueId, null, null, [$errorDetails]),
            // Including ConstraintError::ADDITIONAL_PROPERTIES (an undefined property).
            default => new FormatViolationError($uniqueId, null, null, [$errorDetails]),
        };
    }

    /**
     * @param  SchemaError  $error
     */
    private static function formatValidationErrorDetails(array $error, ?string $constraint): string
    {
        $message = $error['message'] ?? 'Unknown schema validation error';
        $name = $error['property'] ?? '';

        // These messages already name the property.
        $namesProperty = [ConstraintError::TYPE, ConstraintError::ADDITIONAL_PROPERTIES, ConstraintError::REQUIRED, ConstraintError::LENGTH_MAX];

        if ($name === '' || in_array($constraint, $namesProperty, true)) {
            return $message;
        }

        return "Payload {$name} is not valid: ".$message;
    }

    private static function formatValidationExceptionMessage(CallError $callError): string
    {
        $detail = Value::toString($callError->errorDetails[0] ?? null) ?? $callError->errorDescription;

        return $callError->errorCode.': '.$detail;
    }

    /**
     * Convert a payload to what json-schema validates (objects as stdClass, enums as their
     * values), following the schema so that empty JSON objects stay objects.
     *
     * @param  array<mixed>  $schema
     * @param  array<mixed>|null  $rootSchema
     */
    private static function normalizePayload(mixed $payload, array $schema, ?array $rootSchema = null): mixed
    {
        // Enum-typed properties (e.g. BootReason::PowerUp) serialize to their backing value.
        if ($payload instanceof BackedEnum) {
            return $payload->value;
        }

        // Datatypes serialize to their set properties (nulls omitted), as they go on the wire.
        if ($payload instanceof JsonSerializable) {
            $payload = $payload->jsonSerialize();
        }

        // Objects (message payloads, stdClass, datatypes) are normalized like decoded arrays
        // so nested arrays inside them are converted according to the schema too.
        if (is_object($payload)) {
            $payload = get_object_vars($payload);
        }

        if (! is_array($payload)) {
            return $payload;
        }

        $rootSchema ??= $schema;
        $schema = self::resolveSchema($schema, $rootSchema);

        if (($schema['type'] ?? null) === 'array') {
            $itemSchema = is_array($schema['items'] ?? null) ? $schema['items'] : [];

            return array_map(
                static fn (mixed $item): mixed => self::normalizePayload($item, $itemSchema, $rootSchema),
                $payload,
            );
        }

        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        $normalizedPayload = [];

        foreach ($payload as $key => $value) {
            $propertySchema = is_array($properties[$key] ?? null) ? $properties[$key] : [];
            $normalizedPayload[$key] = self::normalizePayload($value, $propertySchema, $rootSchema);
        }

        return (object) $normalizedPayload;
    }

    /**
     * @param  array<mixed>  $schema
     */
    private static function getSchemaId(array $schema): ?string
    {
        $schemaId = $schema['$id'] ?? $schema['id'] ?? null;

        return is_string($schemaId) ? $schemaId : null;
    }

    /**
     * @param  array<mixed>  $schema
     */
    private static function containsUnsupportedSchemaId(array $schema): bool
    {
        if (isset($schema['$id']) && is_string($schema['$id']) && str_starts_with($schema['$id'], 'urn:OCPP:')) {
            return true;
        }

        return array_any($schema, static fn (mixed $value): bool => is_array($value) && self::containsUnsupportedSchemaId($value));
    }

    /**
     * @param  array<mixed>  $schema
     * @return array<mixed>
     */
    private static function normalizeUnsupportedSchemaIds(array $schema): array
    {
        if (isset($schema['$id']) && is_string($schema['$id']) && str_starts_with($schema['$id'], 'urn:OCPP:')) {
            $schema['$id'] = self::normalizeSchemaId($schema['$id']);
        }

        foreach ($schema as $key => $value) {
            if (is_array($value)) {
                $schema[$key] = self::normalizeUnsupportedSchemaIds($value);
            }
        }

        return $schema;
    }

    private static function normalizeSchemaId(string $schemaId): string
    {
        $normalizedId = substr($schemaId, strlen('urn:OCPP:'));

        return 'ocpp://'.str_replace(':', '/', $normalizedId);
    }

    /**
     * @param  array<mixed>  $schema
     * @param  array<mixed>  $rootSchema
     * @return array<mixed>
     */
    private static function resolveSchema(array $schema, array $rootSchema): array
    {
        $reference = $schema['$ref'] ?? null;

        return is_string($reference) ? self::resolveSchemaReference($reference, $rootSchema) : $schema;
    }

    /**
     * Resolve a local reference such as "#/definitions/IdTokenType", the only kind the OCPP
     * schemas use. A reference that does not resolve gives an empty schema.
     *
     * @param  array<mixed>  $rootSchema
     * @return array<mixed>
     */
    private static function resolveSchemaReference(string $reference, array $rootSchema): array
    {
        $current = $rootSchema;

        foreach (explode('/', substr($reference, 2)) as $segment) {
            $current = is_array($current) ? ($current[str_replace(['~1', '~0'], ['/', '~'], $segment)] ?? null) : null;
        }

        return is_array($current) ? $current : [];
    }

    private static function isErrorType(mixed $messageTypeId): bool
    {
        return in_array(
            MessageTypeId::tryFromInt($messageTypeId),
            [MessageTypeId::CallError, MessageTypeId::CallResultError],
            true,
        );
    }

    /**
     * Validate that a CallError has a well-formed structure.
     *
     * CallError messages don't have a JSON schema to validate against, but they need
     * a non-empty errorCode (errorDescription and errorDetails are typed properties).
     */
    private static function validateCallErrorStructure(CallError $callError, bool $throwException): bool|CallError
    {
        if (isset($callError->errorCode) && $callError->errorCode !== '') {
            return true;
        }

        $protocolError = new ProtocolError(
            $callError->uniqueId,
            null,
            null,
            ['The property errorCode is required and must be a non-empty string'],
        );

        if ($throwException) {
            throw new Exception(self::formatValidationExceptionMessage($protocolError));
        }

        return $protocolError;
    }

    /**
     * Normalize a raw CallError array into a CallError object.
     *
     * Handles both the raw OCPP wire format [4, uniqueId, errorCode, ...]
     * and the parsed message format ['messageTypeID' => 4, ...].
     *
     * @param  array<mixed>  $message
     */
    private static function normalizeCallErrorArray(array $message): CallError
    {
        // Raw OCPP wire format: [4|5, uniqueId, errorCode, errorDescription, errorDetails]
        if (self::isErrorType($message[0] ?? null)) {
            return CallError::fromArray($message);
        }

        // Parsed message format from parseJsonMessage:
        // ['messageTypeID' => 4|5, 'uniqueId' => '...', 'action' => '...', 'payload' => [...]]
        $messageTypeId = $message['messageTypeID'] ?? MessageTypeId::CallError->value;
        $uniqueId = $message['uniqueId'] ?? '';
        $errorCode = $message['action'] ?? $message['errorCode'] ?? '';
        $payload = $message['payload'] ?? [];

        // The payload in parsed format may contain errorDescription and errorDetails
        $errorDescription = '';
        $errorDetails = [];

        if (is_array($payload)) {
            $errorDescription = $payload['errorDescription'] ?? '';
            $errorDetails = $payload['errorDetails'] ?? [];
        }

        // Rebuild into raw format for CallError::fromArray
        $raw = [$messageTypeId, $uniqueId, $errorCode, $errorDescription, $errorDetails];

        return CallError::fromArray($raw);
    }
}
