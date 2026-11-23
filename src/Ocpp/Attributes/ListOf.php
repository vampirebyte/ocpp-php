<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp\Attributes;

use Attribute;
use SolutionForest\OcppPhp\Ocpp\Datatype;

/**
 * Marks an array property as a list of Datatype objects, so decoded JSON arrays
 * are hydrated into instances of $class.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ListOf
{
    /**
     * @param  class-string<Datatype>  $class
     */
    public function __construct(public string $class) {}
}
