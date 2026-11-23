<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp;

use Exception;
use ReflectionClass;
use SolutionForest\OcppPhp\Ocpp\Messages\Call;
use SolutionForest\OcppPhp\Ocpp\Messages\CallError;
use SolutionForest\OcppPhp\Ocpp\Messages\CallResult;
use SolutionForest\OcppPhp\Ocpp\Messages\MessageTypeId;
use SolutionForest\OcppPhp\Ocpp\Messages\Send;
use SolutionForest\OcppPhp\Ocpp\Support\Value;

class JsonSchemaRegistry
{
    /**
     * @var list<string>
     */
    private array $schemaDirs = ['v1.6', 'v2.0.1', 'v2.1'];

    /**
     * Create a Call, CallResult, or CallError object from a raw OCPP message array.
     *
     * For Call messages: [2, UniqueId, action, payload]
     * For CallResult messages: [3, UniqueId, payload] (requires action parameter)
     * For CallError messages: [4, UniqueId, errorCode, errorDescription, errorDetails]
     * For CallResultError messages (OCPP 2.1): [5, UniqueId, errorCode, errorDescription, errorDetails]
     * For Send messages (OCPP 2.1): [6, UniqueId, action, payload]
     *
     * @param  array<mixed>  $message  The raw OCPP message array
     * @param  string  $version  The OCPP version (e.g., 'v1.6', 'v2.0.1', 'v2.1')
     * @param  string|null  $action  Required for CallResult messages (the action name this is a response to)
     *
     * @throws Exception
     */
    public function createFromArray(array $message, string $version, ?string $action = null): Call|CallResult|CallError
    {
        return match (MessageTypeId::tryFromInt($message[0] ?? null)) {
            MessageTypeId::Call, MessageTypeId::Send => Call::fromArray($message, $version),
            MessageTypeId::CallResult => $action === null
                ? throw new Exception('Action parameter is required for CallResult messages')
                : CallResult::fromArray($message, $action, $version),
            MessageTypeId::CallError, MessageTypeId::CallResultError => CallError::fromArray($message),
            null => throw new Exception('Invalid message type: '.Value::describe($message[0] ?? null).'. Expected 2 (Call), 3 (CallResult), 4 (CallError), 5 (CallResultError) or 6 (Send)'),
        };
    }

    /**
     * @param  Call|CallResult|array<mixed>  $message  A message, or a parsed message ['messageTypeID' => 2|3|6, 'action' => ..., 'payload' => ...]
     * @return array<mixed>
     *
     * @throws Exception
     */
    public function getSchema(Call|CallResult|array $message, string $version): array
    {
        if (is_array($message)) {
            $action = Value::toString($message['action'] ?? null) ?? '';
            $type = match (MessageTypeId::tryFromInt($message['messageTypeID'] ?? null)) {
                MessageTypeId::Call => 'Calls',
                MessageTypeId::Send => 'Sends',
                MessageTypeId::CallResult => 'CallResults',
                default => throw new Exception('Unvalid message type for validation: '.Value::describe($message['messageTypeID'] ?? null)),
            };

            return $this->requireSchema($action.'.json', $type, $version);
        }

        $type = match (true) {
            $message instanceof Send => 'Sends',
            $message instanceof Call => 'Calls',
            default => 'CallResults',
        };

        return $this->requireSchema(new ReflectionClass($message)->getShortName().'.json', $type, $version);
    }

    /**
     * Load the schema of an action, e.g. ('BootNotification.json', 'Calls', 'v2.1') loads
     * BootNotificationRequest.json: Calls get the "Request" suffix (except in OCPP 1.6)
     * and CallResults the "Response" suffix.
     *
     * @return array<mixed>
     *
     * @throws Exception
     */
    public function loadSchema(string $filename, string $type, string $version = 'latest'): ?array
    {
        $name = str_ends_with($filename, '.json') ? substr($filename, 0, -5) : $filename;
        $suffix = match (true) {
            $type === 'CallResults' => 'Response',
            $type === 'Calls' && str_replace('.', '', $version) !== 'v16' => 'Request',
            default => '',
        };

        if (! str_ends_with($name, $suffix)) {
            $name .= $suffix;
        }

        $filePath = $this->getSchemaPath($name.'.json', $version);

        if (! file_exists($filePath)) {
            throw new Exception("Schema not found: {$name}.json in version {$version}");
        }

        return $this->loadSchemaFromFile($filePath);
    }

    public function findLatestVersion(): ?string
    {
        // Simple version comparison (assumes numeric versioning like v1, v2, etc.)
        usort($this->schemaDirs, static fn (string $a, string $b): int => strnatcmp(basename($a), basename($b)));

        return end($this->schemaDirs) ?: null;
    }

    /**
     * @deprecated Use createFromArray(), which also handles SEND, CALLERROR and CALLRESULTERROR messages.
     *
     * @param  array<mixed>  $message  The raw OCPP message [2, UniqueId, action, payload]
     *
     * @throws Exception
     */
    public function createCallFromArray(array $message, string $version): Call
    {
        if (MessageTypeId::tryFromInt($message[0] ?? null) !== MessageTypeId::Call) {
            throw new Exception('Invalid message type for Call');
        }

        return Call::fromArray($message, $version);
    }

    /**
     * @deprecated Use createFromArray().
     *
     * @param  array<mixed>  $message  The raw OCPP message [3, UniqueId, payload]
     *
     * @throws Exception
     */
    public function createCallResultFromArray(array $message, string $action, string $version): CallResult
    {
        if (MessageTypeId::tryFromInt($message[0] ?? null) !== MessageTypeId::CallResult) {
            throw new Exception('Invalid message type for CallResult');
        }

        return CallResult::fromArray($message, $action, $version);
    }

    /**
     * loadSchema() may be overridden by a subclass, whose signature allows returning null.
     *
     * @return array<mixed>
     *
     * @throws Exception
     */
    private function requireSchema(string $filename, string $type, string $version): array
    {
        return $this->loadSchema($filename, $type, $version) ?? throw new Exception("Schema not found: {$filename} in version {$version}");
    }

    private function getSchemaPath(string $filename, string $version): string
    {
        if ($version === 'latest') {
            $version = (string) $this->findLatestVersion();
        }

        return __DIR__.'/'.str_replace('.', '', $version).'/Schemas/'.$filename;
    }

    /**
     * @return array<mixed>
     *
     * @throws Exception
     */
    private function loadSchemaFromFile(string $filePath): array
    {
        $schema = json_decode((string) file_get_contents($filePath), true, 512, JSON_THROW_ON_ERROR);

        return is_array($schema) ? $schema : throw new Exception("Invalid schema: {$filePath}");
    }
}
