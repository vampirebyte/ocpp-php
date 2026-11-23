<?php

declare(strict_types=1);

/**
 * OCPP 2.0.1 Charging Station (WebSocket client): connects to the example CSMS,
 * sends a few Calls, prints the answers and exits.
 *
 *   php src/Examples/v201/CentralSystem.php &
 *   php src/Examples/v201/ChargePoint.php [--uri=ws://127.0.0.1:8080]
 *
 * Needs phrity/websocket (composer require phrity/websocket).
 */

use SolutionForest\OcppPhp\Ocpp\JsonSchemaValidator;
use SolutionForest\OcppPhp\Ocpp\Messages\Call;
use SolutionForest\OcppPhp\Ocpp\v201\Calls\BootNotification;
use SolutionForest\OcppPhp\Ocpp\v201\Calls\ClearCache;
use SolutionForest\OcppPhp\Ocpp\v201\Calls\Heartbeat;
use SolutionForest\OcppPhp\Ocpp\v201\Calls\StatusNotification;
use SolutionForest\OcppPhp\Ocpp\v201\Enums\BootReason;
use SolutionForest\OcppPhp\Ocpp\v201\Enums\ConnectorStatus;
use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Exception\ExceptionInterface;
use WebSocket\Message\Text;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

require __DIR__.'/../../../vendor/autoload.php';

const OCPP_VERSION = 'v2.0.1';

if (! class_exists(Client::class)) {
    throw new Exception('phrity/websocket is required for examples. Run: composer require phrity/websocket');
}

/** @var list<Call> $calls */
$calls = [
    new BootNotification([
        'reason' => BootReason::PowerUp,
        'chargingStation' => ['model' => 'MyModel', 'vendorName' => 'MyVendor'],
    ]),
    new Heartbeat(),
    new StatusNotification([
        'timestamp' => date('c'),
        'connectorStatus' => ConnectorStatus::Available,
        'evseId' => 1,
        'connectorId' => 1,
    ]),
    // Not handled by the example CSMS: it answers with a CALLERROR (NotImplemented).
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
        echo "Charging Station: connected\n";

        foreach ($calls as $call) {
            JsonSchemaValidator::validate($call, OCPP_VERSION);
            $json = (string) json_encode($call->toArray());

            echo "Charging Station -> CSMS: {$json}\n";
            $client->text($json);
        }
    })
    ->onText(function (Client $client, Connection $connection, Text $message) use (&$pending): void {
        echo "CSMS -> Charging Station: {$message->getContent()}\n";

        if (--$pending === 0) {
            $client->close();
            $client->stop();
        }
    })
    ->onError(function (Client $client, ?Connection $connection, ExceptionInterface $exception): void {
        echo "Charging Station: error: {$exception->getMessage()}\n";
        $client->stop();
    })
    ->start();
