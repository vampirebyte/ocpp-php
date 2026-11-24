<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v16\Enums;

/**
 * Values of the RecurrencyKindType field of a value in RemoteStartTransaction , SetChargingProfile.
 */
enum RecurrencyKindType: string
{
    case Daily = 'Daily';
    case Weekly = 'Weekly';
}
