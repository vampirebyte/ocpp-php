<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Scripts;

require_once __DIR__.'/../../vendor/autoload.php';
use Nette\PhpGenerator\InterfaceType;

/**
 * Generates OcppServiceInterface: a handle<Action>() method for every Call and SEND.
 *
 * @extends SchemaProcessor<list<string>>
 */
class SchemaToInterface extends SchemaProcessor
{
    public InterfaceType $interface;

    public function main(): void
    {
        $this->readVersion();

        $this->interface = new InterfaceType('OcppServiceInterface');
        foreach ($this->schemaFiles() as $schema) {
            $classes = $this->parseSchema($schema);
            $this->processClasses($classes);
        }

        $this->generateDataClass($this->interface, 'Interfaces');
    }

    protected function processClasses(array $classes): void
    {
        foreach ($classes as $class) {
            echo "Processing {$class}\n";
            $this->interface->addMethod('handle'.$class)->setStatic()
                ->setPublic()
                ->setReturnType('array')
                ->addComment('@param array<mixed> $message')
                ->addComment('@return array<mixed>')
                ->addParameter('message')->setType('array');
        }
    }

    protected function parseSchema(string $schema): array
    {
        $title = $this->getNameFromSchema($schema);

        if (str_ends_with($title, 'Request')) {
            return [substr($title, 0, -7)];
        }

        if (str_ends_with($title, 'Response')) {
            return [];
        }

        return [$title];
    }
}
