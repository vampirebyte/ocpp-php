<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\Exceptions;

use SolutionForest\OcppPhp\Ocpp\Messages\CallError;

// Raised when a CALLERROR is received with unknown error code.
class UnknownCallErrorCodeError extends CallError {}
