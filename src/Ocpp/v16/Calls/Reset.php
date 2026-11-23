<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v16\Calls;

use SolutionForest\OcppPhp\Ocpp\Messages\Call;
use SolutionForest\OcppPhp\Ocpp\v16\Enums\ResetType;

class Reset extends Call
{
    public string|ResetType $type;
}
