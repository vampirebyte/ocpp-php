<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v16\Calls;

use SolutionForest\OcppPhp\Ocpp\Messages\Call;

class GetConfiguration extends Call
{
    /**
     * @var list<string>|null
     */
    public ?array $key = null;
}
