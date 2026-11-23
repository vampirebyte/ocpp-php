<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v16\Calls;

use SolutionForest\OcppPhp\Ocpp\Messages\Call;
use SolutionForest\OcppPhp\Ocpp\v16\Datatypes\ChargingProfile;

class SetChargingProfile extends Call
{
    public int $connectorId;

    public ChargingProfile $csChargingProfiles;
}
