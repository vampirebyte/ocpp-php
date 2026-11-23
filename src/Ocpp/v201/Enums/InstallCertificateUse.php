<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v201\Enums;

/**
 * Values of the InstallCertificateUse field of a value in InstallCertificateRequest.
 */
enum InstallCertificateUse: string
{
    case V2GRootCertificate = 'V2GRootCertificate';
    case MORootCertificate = 'MORootCertificate';
    case CSMSRootCertificate = 'CSMSRootCertificate';
    case ManufacturerRootCertificate = 'ManufacturerRootCertificate';
}
