<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v201\Enums;

/**
 * Values of the ReservationUpdateStatus field of a value in ReservationStatusUpdateRequest.
 */
enum ReservationUpdateStatus: string
{
    case Expired = 'Expired';
    case Removed = 'Removed';
}
