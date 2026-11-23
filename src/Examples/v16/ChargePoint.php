<?php

declare(strict_types=1);

/**
 * OCPP 1.6 Charge Point (WebSocket client): connects to the example Central System,
 * sends a few Calls, prints the answers and exits.
 *
 *   php src/Examples/v16/CentralSystem.php &
 *   php src/Examples/v16/ChargePoint.php [--uri=ws://127.0.0.1:8080]
 *
 * Needs phrity/websocket (composer require phrity/websocket).
 */

use SolutionForest\OcppPhp\Ocpp\JsonSchemaValidator;
use SolutionForest\OcppPhp\Ocpp\Messages\Call;
use SolutionForest\OcppPhp\Ocpp\v16\Calls\BootNotification;
use SolutionForest\OcppPhp\Ocpp\v16\Calls\ClearCache;
use SolutionForest\OcppPhp\Ocpp\v16\Calls\Heartbeat;
use SolutionForest\OcppPhp\Ocpp\v16\Calls\StatusNotification;
use SolutionForest\OcppPhp\Ocpp\v16\Enums\ChargePointErrorCode;
use SolutionForest\OcppPhp\Ocpp\v16\Enums\ChargePointStatus;
use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Exception\ExceptionInterface;
use WebSocket\Message\Text;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

require __DIR__.'/../../../vendor/autoload.php';

const OCPP_VERSION = 'v1.6';

if (! class_exists(Client::class)) {
    throw new Exception('phrity/websocket is required for examples. Run: composer require phrity/websocket');
}

/** @var list<Call> $calls */
$calls = [
    new BootNotification(['chargePointVendor' => 'MyVendor', 'chargePointModel' => 'MyModel']),
    new Heartbeat(),
    new StatusNotification([
        'connectorId' => 1,
        'errorCode' => ChargePointErrorCode::NoError,
        'status' => ChargePointStatus::Available,
        'timestamp' => date('c'),
    ]),
    // Not handled by the example Central System: it answers with a CALLERROR (NotImplemented).
    new ClearCache(),
];
$pending = count($calls);

$options = getopt('', ['uri:']);
$uri = is_string($options['uri'] ?? null) ? $options['uri'] : 'ws://127.0.0.1:8080';

$client = new Client($uri);
$client
    ->addMiddleware(new CloseHandler())
    ->addMiddleware(new PingResponder());

$client
    ->onHandshake(function (Client $client) use ($calls): void {
        echo "Charge Point: connected\n";

        foreach ($calls as $call) {
            JsonSchemaValidator::validate($call, OCPP_VERSION);
            $json = (string) json_encode($call->toArray());

            echo "Charge Point -> Central System: {$json}\n";
            $client->text($json);
        }
    })
    ->onText(function (Client $client, Connection $connection, Text $message) use (&$pending): void {
        echo "Central System -> Charge Point: {$message->getContent()}\n";

        if (--$pending === 0) {
            $client->close();
            $client->stop();
        }
    })
    ->onError(function (Client $client, ?Connection $connection, ExceptionInterface $exception): void {
        echo "Charge Point: error: {$exception->getMessage()}\n";
        $client->stop();
    })
    ->start();
