<?php

declare(strict_types=1);

/**
 * OCPP 1.6 Central System (WebSocket server) that answers the Calls of the example Charge Point.
 *
 *   php src/Examples/v16/CentralSystem.php [--port=8080]
 *
 * Needs phrity/websocket (composer require phrity/websocket).
 */

use SolutionForest\OcppPhp\Ocpp\Exceptions\NotImplementedError;
use SolutionForest\OcppPhp\Ocpp\JsonSchemaRegistry;
use SolutionForest\OcppPhp\Ocpp\JsonSchemaValidator;
use SolutionForest\OcppPhp\Ocpp\Messages\Call;
use SolutionForest\OcppPhp\Ocpp\Messages\CallError;
use SolutionForest\OcppPhp\Ocpp\Messages\CallResult;
use SolutionForest\OcppPhp\Ocpp\v16\CallResults\BootNotification as BootNotificationResult;
use SolutionForest\OcppPhp\Ocpp\v16\CallResults\Heartbeat as HeartbeatResult;
use SolutionForest\OcppPhp\Ocpp\v16\CallResults\StatusNotification as StatusNotificationResult;
use SolutionForest\OcppPhp\Ocpp\v16\Calls\BootNotification;
use SolutionForest\OcppPhp\Ocpp\v16\Calls\Heartbeat;
use SolutionForest\OcppPhp\Ocpp\v16\Calls\StatusNotification;
use SolutionForest\OcppPhp\Ocpp\v16\Enums\RegistrationStatus;
use WebSocket\Connection;
use WebSocket\Exception\ExceptionInterface;
use WebSocket\Message\Text;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;
use WebSocket\Server;

require __DIR__.'/../../../vendor/autoload.php';

const OCPP_VERSION = 'v1.6';

if (! class_exists(Server::class)) {
    throw new Exception('phrity/websocket is required for examples. Run: composer require phrity/websocket');
}

/**
 * The response to a Call: a CallResult for the actions this example handles, a CALLERROR otherwise.
 */
function respond(Call $call): CallResult|CallError
{
    return match (true) {
        $call instanceof BootNotification => new BootNotificationResult($call->getUniqueId(), [
            'status' => RegistrationStatus::Accepted,
            'currentTime' => date('c'),
            'interval' => 60,
        ]),
        $call instanceof Heartbeat => new HeartbeatResult($call->getUniqueId(), ['currentTime' => date('c')]),
        $call instanceof StatusNotification => new StatusNotificationResult($call->getUniqueId()),
        default => new NotImplementedError($call->getUniqueId()),
    };
}

/**
 * Parse a message received from the Charge Point and build the JSON of the answer,
 * or null for messages that are not answered (CallResults and CallErrors).
 */
function answer(JsonSchemaRegistry $registry, string $data): ?string
{
    $message = json_decode($data, true);

    try {
        $call = $registry->createFromArray(is_array($message) ? $message : [], OCPP_VERSION);
    } catch (Exception $exception) {
        // E.g. an action this library does not know: answer with a CALLERROR.
        $uniqueId = is_array($message) && is_string($message[1] ?? null) ? $message[1] : '';

        return (string) json_encode(new NotImplementedError($uniqueId, null, $exception->getMessage())->toArray());
    }

    if (! $call instanceof Call) {
        return null;
    }

    JsonSchemaValidator::validate($call, OCPP_VERSION);

    $response = respond($call);
    JsonSchemaValidator::validate($response, OCPP_VERSION);

    return (string) json_encode($response->toArray());
}

$options = getopt('', ['port:']);
$port = is_string($options['port'] ?? null) ? (int) $options['port'] : 8080;
$registry = new JsonSchemaRegistry();

$server = new Server($port);
$server
    ->addMiddleware(new CloseHandler())
    ->addMiddleware(new PingResponder());

echo "Central System: listening on port {$server->getPort()}\n";

$server
    ->onHandshake(function (Server $server, Connection $connection): void {
        echo "Central System: {$connection->getRemoteName()} connected\n";
    })
    ->onDisconnect(function (Server $server, Connection $connection): void {
        echo "Central System: {$connection->getRemoteName()} disconnected\n";
    })
    ->onText(function (Server $server, Connection $connection, Text $message) use ($registry): void {
        echo "Charge Point -> Central System: {$message->getContent()}\n";

        $response = answer($registry, $message->getContent());
        if ($response !== null) {
            echo "Central System -> Charge Point: {$response}\n";
            $connection->text($response);
        }
    })
    ->onError(function (Server $server, ?Connection $connection, ExceptionInterface $exception): void {
        echo "Central System: error: {$exception->getMessage()}\n";
    })
    ->start();
