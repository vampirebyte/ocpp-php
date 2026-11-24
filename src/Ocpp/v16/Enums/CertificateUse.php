<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v16\Enums;

/**
 * Values of the CertificateUse field of a value in GetInstalledCertificateIds , InstallCertificate.
 */
enum CertificateUse: string
{
    case CentralSystemRootCertificate = 'CentralSystemRootCertificate';
    case ManufacturerRootCertificate = 'ManufacturerRootCertificate';
}
