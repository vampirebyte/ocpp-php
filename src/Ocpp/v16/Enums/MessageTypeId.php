<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\v16\Enums;

use SolutionForest\OcppPhp\Ocpp\Messages\MessageTypeId as SharedMessageTypeId;

/*
 * @deprecated Use \SolutionForest\OcppPhp\Ocpp\Messages\MessageTypeId, shared by all OCPP versions.
 *
 * This name is a class_alias of the shared enum, registered when the shared enum is loaded
 * (see the end of src/Ocpp/Messages/MessageTypeId.php). The autoloader only reaches this file
 * when the old name is used first: loading the shared enum then registers the alias.
 */
enum_exists(SharedMessageTypeId::class);
