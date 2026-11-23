<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\Messages;

use Exception;
use SolutionForest\OcppPhp\Ocpp\Support\Value;

abstract class CallResult extends Message
{
    public int $messageTypeID = MessageTypeId::CallResult->value;

    /**
     * @param  array<mixed>|null  $payload  Decoded JSON (json_decode(..., true)) or named values; nested objects may be arrays or Datatypes.
     */
    public function __construct(string $uniqueId, ?array $payload = null)
    {
        $this->uniqueId = $uniqueId;

        $this->fill($payload);
    }

    /**
     * Create a CallResult object from a raw OCPP message array.
     *
     * @param  array<mixed>  $message  The raw OCPP message [messageTypeID, UniqueId, payload]
     * @param  string  $action  The action name this is a response to
     * @param  string  $version  The OCPP version (e.g., 'v1.6', 'v2.0.1', 'v2.1')
     *
     * @throws Exception
     */
    public static function fromArray(array $message, string $action, string $version): static
    {
        if (MessageTypeId::tryFromInt($message[0] ?? null) !== MessageTypeId::CallResult) {
            throw new Exception('Invalid message type for CallResult, expected 3 got '.Value::describe($message[0] ?? null));
        }

        $class = 'SolutionForest\\OcppPhp\\Ocpp\\'.str_replace('.', '', $version).'\\CallResults\\'.$action;

        if (! is_a($class, static::class, true)) {
            throw new Exception("Unknown call result action: {$action}");
        }

        $uniqueId = Value::toString($message[1] ?? null);
        if ($uniqueId === null) {
            throw new Exception("Invalid unique id for {$action}: ".Value::describe($message[1] ?? null));
        }

        $payload = $message[2] ?? [];
        if (! is_array($payload)) {
            throw new Exception("Invalid payload for {$action}, expected an object got ".Value::describe($payload));
        }

        return new $class($uniqueId, $payload);
    }

    /**
     * @return array{int, string, object}
     */
    public function toArray(): array
    {
        return [
            $this->messageTypeID,
            $this->uniqueId,
            $this->getPayload(),
        ];
    }
}
