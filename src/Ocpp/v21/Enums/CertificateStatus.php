<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v21\Enums;

/**
 * Values of the CertificateStatus field of a value in GetCertificateChainStatusResponse.
 */
enum CertificateStatus: string
{
    case Good = 'Good';
    case Revoked = 'Revoked';
    case Unknown = 'Unknown';
    case Failed = 'Failed';
}
