<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v21\Enums;

/**
 * Values of the Update field of a value in SendLocalListRequest.
 */
enum Update: string
{
    case Differential = 'Differential';
    case Full = 'Full';
}
