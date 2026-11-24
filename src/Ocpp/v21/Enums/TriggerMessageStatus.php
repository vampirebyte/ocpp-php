<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v21\Enums;

/**
 * Values of the TriggerMessageStatus field of a value in TriggerMessageResponse.
 */
enum TriggerMessageStatus: string
{
    case Accepted = 'Accepted';
    case Rejected = 'Rejected';
    case NotImplemented = 'NotImplemented';
}
