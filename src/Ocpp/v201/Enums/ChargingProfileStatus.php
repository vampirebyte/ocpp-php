<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v201\Enums;

/**
 * Values of the ChargingProfileStatus field of a value in SetChargingProfileResponse.
 */
enum ChargingProfileStatus: string
{
    case Accepted = 'Accepted';
    case Rejected = 'Rejected';
}
