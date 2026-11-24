<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v21\Enums;

/**
 * Values of the OperationalStatus field of a value in ChangeAvailabilityRequest.
 */
enum OperationalStatus: string
{
    case Inoperative = 'Inoperative';
    case Operative = 'Operative';
}
