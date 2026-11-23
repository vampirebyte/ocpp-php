<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Ocpp;

use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use SolutionForest\OcppPhp\Ocpp\Attributes\ListOf;

/**
 * Fills typed properties from decoded JSON (json_decode(..., true)):
 * - a property typed with a Datatype class receives an instance of it,
 * - a #[ListOf(X::class)] array receives a list of X instances,
 * - a free-form `object` property (e.g. customData) receives a stdClass.
 * Values that are already objects, and scalars, are assigned as they are.
 */
trait HydratesProperties
{
    /**
     * @param  array<mixed>|null  $data
     */
    protected function fill(?array $data): void
    {
        foreach ($data ?? [] as $key => $value) {
            // Integer keys (a JSON array instead of an object) match no property and are skipped.
            $this->setPayloadProperty((string) $key, $value);
        }
    }

    protected function setPayloadProperty(string $key, mixed $value): void
    {
        if (! property_exists($this, $key)) {
            return;
        }

        if (is_array($value)) {
            $value = self::hydrate(new ReflectionProperty($this, $key), $value);
        }

        $this->$key = $value;
    }

    /**
     * @param  array<mixed>  $value
     */
    private static function hydrate(ReflectionProperty $property, array $value): mixed
    {
        $listOf = $property->getAttributes(ListOf::class)[0] ?? null;
        if ($listOf !== null) {
            $class = $listOf->newInstance()->class;

            return array_map(
                static fn (mixed $item): mixed => is_array($item) ? new $class($item) : $item,
                $value,
            );
        }

        $type = $property->getType();
        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];

        foreach ($types as $named) {
            if (! $named instanceof ReflectionNamedType) {
                continue;
            }

            $name = $named->getName();
            if (is_subclass_of($name, Datatype::class)) {
                return new $name($value);
            }

            if ($name === 'object') {
                return (object) $value;
            }
        }

        return $value;
    }
}
