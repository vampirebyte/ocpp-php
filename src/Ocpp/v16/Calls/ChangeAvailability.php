<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v16\Calls;

use SolutionForest\OcppPhp\Ocpp\Messages\Call;
use SolutionForest\OcppPhp\Ocpp\v16\Enums\AvailabilityType;

class ChangeAvailability extends Call
{
    public int $connectorId;

    public string|AvailabilityType $type;
}
