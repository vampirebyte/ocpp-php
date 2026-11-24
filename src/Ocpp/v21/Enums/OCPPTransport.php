<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v21\Enums;

/**
 * Values of the OCPPTransport field of a value in SetNetworkProfileRequest.
 */
enum OCPPTransport: string
{
    case SOAP = 'SOAP';
    case JSON = 'JSON';
}
