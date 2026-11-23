<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Scripts;

use RuntimeException;

require_once __DIR__.'/../../vendor/autoload.php';

use Nette\PhpGenerator\EnumType;

/**
 * Generates the Enums: named "...EnumType" definitions and, for core OCPP 1.6, the inline
 * enums named in INLINE_ENUMS. An enum used by several schemas gets the union of their values.
 *
 * @extends SchemaProcessor<array<string, EnumType>>
 */
class SchemaToEnum extends SchemaProcessor
{
    /**
     * @var array<string, EnumType>
     */
    protected array $existingEnums = [];

    protected function processClasses(array $classes): void
    {
        foreach ($classes as $class) {
            $this->generateDataClass($class, 'Enums');
        }
    }

    protected function parseSchema(string $schema): array
    {
        $schemaData = self::readSchema($schema);

        $title = $this->getNameFromSchema($schema);

        foreach (self::node($schemaData['definitions'] ?? null) as $enumType => $value) {
            $enumType = (string) $enumType;
            if (! str_contains($enumType, 'EnumType')) {
                continue;
            }

            $typeName = str_replace('EnumType', '', $enumType);
            $values = self::stringList(self::node($value)['enum'] ?? null);

            if (! isset($this->existingEnums[$typeName])) {
                $enum = new EnumType($typeName);

                $enum->addComment("Values of the {$typeName} field of a value in {$title}.");
                $this->mergeCases($enum, $values);

                $this->existingEnums[$typeName] = $enum;
            } else {
                echo "Enum {$typeName} already exists\n";

                $enum = $this->existingEnums[$typeName];
                $comment = substr((string) $enum->getComment(), 0, -1);
                $enum->setComment($comment." , {$title}.");

                // Schemas may define the same enum with different values: keep the union.
                $this->mergeCases($enum, $values);
            }
        }

        // Inline enums (core OCPP 1.6): named via SchemaProcessor::INLINE_ENUMS, values merged.
        foreach (self::findInlineEnums($this->version, $title, $schemaData) as $inline) {
            $typeName = $inline['enum'];
            $values = $inline['values'];

            if ($typeName === null) {
                throw new RuntimeException(
                    "Unmapped inline enum {$title}:{$inline['path']} in {$this->version}. "
                    .'Add its name to SchemaProcessor::INLINE_ENUMS.'
                );
            }

            if (! isset($this->existingEnums[$typeName])) {
                $this->existingEnums[$typeName] = new EnumType($typeName)
                    ->addComment("Values of the {$typeName} field of a value in {$title}.");
            } else {
                $enum = $this->existingEnums[$typeName];
                $comment = substr((string) $enum->getComment(), 0, -1);
                // A schema may use the same enum on several properties; list it once.
                if (! preg_match('/(in | , )'.preg_quote($title, '/').'( ,|$)/', $comment)) {
                    $enum->setComment($comment." , {$title}.");
                }
            }

            $this->mergeCases($this->existingEnums[$typeName], $values);
        }

        return $this->existingEnums;
    }

    /**
     * @param  list<string>  $values
     */
    private function mergeCases(EnumType $enum, array $values): void
    {
        foreach ($values as $enumAttr) {
            $enumAttrTitle = preg_replace('/[^a-zA-Z0-9]+/', '', $enumAttr) ?? $enumAttr;
            if (! isset($enum->getCases()[$enumAttrTitle])) {
                $enum->addCase($enumAttrTitle, $enumAttr);
            }
        }
    }
}
