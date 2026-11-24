<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v21\Enums;

/**
 * Values of the PriorityChargingStatus field of a value in UsePriorityChargingResponse.
 */
enum PriorityChargingStatus: string
{
    case Accepted = 'Accepted';
    case Rejected = 'Rejected';
    case NoProfile = 'NoProfile';
}
