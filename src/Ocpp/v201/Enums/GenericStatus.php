<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v201\Enums;

/**
 * Values of the GenericStatus field of a value in GetCompositeScheduleResponse , NotifyEVChargingScheduleResponse , PublishFirmwareResponse , SetMonitoringLevelResponse , SignCertificateResponse.
 */
enum GenericStatus: string
{
    case Accepted = 'Accepted';
    case Rejected = 'Rejected';
}
