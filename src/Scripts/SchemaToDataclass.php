<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Scripts;

require_once __DIR__.'/../../vendor/autoload.php';

use Nette\PhpGenerator\ClassType;

/**
 * Generates the Calls and CallResults: one class per message schema.
 *
 * @extends SchemaProcessor<array{calls: list<ClassType>, callResults: list<ClassType>}>
 */
class SchemaToDataclass extends SchemaProcessor
{
    protected function processClasses(array $classes): void
    {
        foreach ($classes['calls'] as $class) {
            $this->generateDataClass($class, 'Calls');
        }

        foreach ($classes['callResults'] as $class) {
            $this->generateDataClass($class, 'CallResults');
        }
    }

    protected function parseSchema(string $schema): array
    {
        $schemaContent = self::readSchema($schema);

        $title = $this->getNameFromSchema($schema);
        $schemaName = $title;

        $call = false;
        $send = false;

        if (str_ends_with($title, 'Request')) {
            $call = true;
            $title = substr($title, 0, -7);
        } elseif (str_ends_with($title, 'Response')) {
            $title = substr($title, 0, -8);
        } else {
            $call = true;
            // OCPP 1.6 requests have no suffix. From OCPP 2.x, a schema without
            // Request/Response suffix is a one-way SEND message (e.g. NotifyPeriodicEventStream).
            $send = $this->version !== 'v16';
        }

        $class = isset($schemaContent['properties'])
            ? $this->mapPropertiesToClass($schemaContent, $title, $schemaContent, $schemaName)
            : new ClassType($title);

        $class->setExtends($send ? 'Send' : ($call ? 'Call' : 'CallResult'));

        return $call
            ? ['calls' => [$class], 'callResults' => []]
            : ['calls' => [], 'callResults' => [$class]];
    }
}
