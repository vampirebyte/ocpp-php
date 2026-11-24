<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v21\Calls;

use SolutionForest\OcppPhp\Ocpp\Messages\Call;

class NotifyAllowedEnergyTransfer extends Call
{
    /**
     * The transaction for which the allowed energy transfer is allowed.
     */
    public string $transactionId;

    /**
     * Modes of energy transfer that are accepted by CSMS.
     *
     * @var list<string>
     */
    public array $allowedEnergyTransfer;

    /** This class does not get 'AdditionalProperties = false' in the schema generation, so it can be extended with arbitrary JSON properties to allow adding custom data. */
    public ?object $customData = null;
}
