<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\Messages;

use Exception;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use SolutionForest\OcppPhp\Ocpp\Support\Value;

abstract class Call extends Message
{
    public int $messageTypeID = MessageTypeId::Call->value;

    /**
     * @param  array<mixed>|null  $payload  Decoded JSON (json_decode(..., true)) or named values; nested objects may be arrays or Datatypes.
     */
    public function __construct(?array $payload = null)
    {
        if (getenv('OCPP_MESSAGE_UUID', false)) {
            $this->uniqueId = Uuid::uuid4()->toString();
        } else {
            $this->uniqueId = (string) mt_rand(9999999, 9999999999);
        }

        $this->fill($payload);
    }

    /**
     * Create a Call object from a raw OCPP message array.
     *
     * Also accepts OCPP 2.1 SEND messages (messageTypeID 6), which resolve to a Send subclass.
     *
     * @param  array<mixed>  $message  The raw OCPP message [messageTypeID, UniqueId, action, payload]
     * @param  string  $version  The OCPP version (e.g., 'v1.6', 'v2.0.1', 'v2.1')
     *
     * @throws Exception
     */
    public static function fromArray(array $message, string $version): static
    {
        $type = MessageTypeId::tryFromInt($message[0] ?? null);

        if (! in_array($type, [MessageTypeId::Call, MessageTypeId::Send], true)) {
            throw new Exception('Invalid message type for Call, expected 2 or 6 got '.Value::describe($message[0] ?? null));
        }

        $action = Value::toString($message[2] ?? null) ?? '';
        $class = 'SolutionForest\\OcppPhp\\Ocpp\\'.str_replace('.', '', $version).'\\Calls\\'.$action;

        if (! is_a($class, static::class, true)) {
            throw new Exception("Unknown call action: {$action}");
        }

        if ($type === MessageTypeId::Send && ! is_a($class, Send::class, true)) {
            throw new Exception("Action {$action} cannot be sent as a SEND message");
        }

        $uniqueId = Value::toString($message[1] ?? null);
        if ($uniqueId === null) {
            throw new Exception("Invalid unique id for {$action}: ".Value::describe($message[1] ?? null));
        }

        $payload = $message[3] ?? [];
        if (! is_array($payload)) {
            throw new Exception("Invalid payload for {$action}, expected an object got ".Value::describe($payload));
        }

        $call = new $class($payload);
        $call->uniqueId = $uniqueId;

        return $call;
    }

    /**
     * @return array{int, string, string, object}
     */
    public function toArray(): array
    {
        return [
            $this->messageTypeID,
            $this->uniqueId,
            new ReflectionClass($this)->getShortName(),
            $this->getPayload(),
        ];
    }
}
