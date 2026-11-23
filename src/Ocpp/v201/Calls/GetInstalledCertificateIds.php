<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v201\Calls;

use SolutionForest\OcppPhp\Ocpp\Messages\Call;
use SolutionForest\OcppPhp\Ocpp\v201\Enums\GetCertificateIdUse;

class GetInstalledCertificateIds extends Call
{
    /**
     * This class does not get 'AdditionalProperties = false' in the schema generation, so it can be extended with arbitrary JSON properties to allow adding custom data.
     */
    public ?object $customData = null;

    /**
     * Indicates the type of certificates requested. When omitted, all certificate types are requested.
     *
     * @var list<string|GetCertificateIdUse>|null
     */
    public ?array $certificateType = null;
}
