<?php

declare(strict_types=1);

namespace SolutionForest\OcppPhp\Scripts;

use RuntimeException;

require_once __DIR__.'/../../vendor/autoload.php';

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use SolutionForest\OcppPhp\Ocpp\Attributes\ListOf;
use SolutionForest\OcppPhp\Ocpp\Datatype;
use SolutionForest\OcppPhp\Ocpp\Messages\Call;
use SolutionForest\OcppPhp\Ocpp\Messages\CallResult;
use SolutionForest\OcppPhp\Ocpp\Messages\Send;

/**
 * Generates PHP code from the OCPP JSON schemas of one version: each subclass parses every
 * schema (parseSchema) and writes the classes found (processClasses).
 *
 * @template TClasses of array
 *
 * @phpstan-type ResolvedType array{type: ?string, enum: ?string, datatype: ?string, listOf: ?string, itemType: ?string, description: string}
 */
abstract class SchemaProcessor
{
    /** @var list<string> */
    public const array SUPPORTED_VERSIONS = ['v16', 'v201', 'v21'];

    /**
     * Names for enums that schemas define inline on a property, instead of as a named
     * "...EnumType" definition. The core OCPP 1.6 schemas define all their enums this way;
     * the names come from OCPP 1.6 edition 2, chapter 7 "Types". OCPP 2.x has none.
     *
     * Keys, most specific first: "Schema:path", then the path or any trailing part of it,
     * e.g. "idTagInfo.status" also matches SendLocalList's "localAuthorizationList.idTagInfo.status".
     * Path segments are property names; array items are traversed implicitly.
     * Every inline enum must resolve, or generation fails.
     *
     * @var array<string, array<string, string>>
     */
    public const array INLINE_ENUMS = [
        'v16' => [
            // Field names that mean the same type in every schema.
            'idTagInfo.status' => 'AuthorizationStatus',
            'chargingProfilePurpose' => 'ChargingProfilePurposeType',
            'chargingProfileKind' => 'ChargingProfileKindType',
            'recurrencyKind' => 'RecurrencyKindType',
            'chargingRateUnit' => 'ChargingRateUnitType',
            'context' => 'ReadingContext',
            'format' => 'ValueFormat',
            'measurand' => 'Measurand',
            'phase' => 'Phase',
            'location' => 'Location',
            'unit' => 'UnitOfMeasure',
            'errorCode' => 'ChargePointErrorCode',
            'reason' => 'Reason',
            'requestedMessage' => 'MessageTrigger',
            'updateType' => 'UpdateType',

            // "type" and "status" mean a different type per message.
            'ChangeAvailability:type' => 'AvailabilityType',
            'Reset:type' => 'ResetType',
            'BootNotificationResponse:status' => 'RegistrationStatus',
            'CancelReservationResponse:status' => 'CancelReservationStatus',
            'ChangeAvailabilityResponse:status' => 'AvailabilityStatus',
            'ChangeConfigurationResponse:status' => 'ConfigurationStatus',
            'ClearCacheResponse:status' => 'ClearCacheStatus',
            'ClearChargingProfileResponse:status' => 'ClearChargingProfileStatus',
            'DataTransferResponse:status' => 'DataTransferStatus',
            'DiagnosticsStatusNotification:status' => 'DiagnosticsStatus',
            'FirmwareStatusNotification:status' => 'FirmwareStatus',
            'GetCompositeScheduleResponse:status' => 'GetCompositeScheduleStatus',
            'RemoteStartTransactionResponse:status' => 'RemoteStartStopStatus',
            'RemoteStopTransactionResponse:status' => 'RemoteStartStopStatus',
            'ReserveNowResponse:status' => 'ReservationStatus',
            'ResetResponse:status' => 'ResetStatus',
            'SendLocalListResponse:status' => 'UpdateStatus',
            'SetChargingProfileResponse:status' => 'ChargingProfileStatus',
            'StatusNotification:status' => 'ChargePointStatus',
            'TriggerMessageResponse:status' => 'TriggerMessageStatus',
            'UnlockConnectorResponse:status' => 'UnlockStatus',
        ],
    ];

    /**
     * Names for objects that schemas define inline instead of as a named definition (core
     * OCPP 1.6 only), generated as Datatypes. Names from OCPP 1.6 edition 2, chapter 7 "Types".
     * Same key format as INLINE_ENUMS. Every inline object must resolve, or generation fails.
     *
     * @var array<string, array<string, string>>
     */
    public const array INLINE_OBJECTS = [
        'v16' => [
            'idTagInfo' => 'IdTagInfo',
            'chargingProfile' => 'ChargingProfile',
            'csChargingProfiles' => 'ChargingProfile',
            'chargingSchedule' => 'ChargingSchedule',
            'chargingSchedulePeriod' => 'ChargingSchedulePeriod',
            'configurationKey' => 'KeyValue',
            'localAuthorizationList' => 'AuthorizationData',
            'meterValue' => 'MeterValue',
            'transactionData' => 'MeterValue',
            'sampledValue' => 'SampledValue',
        ],
    ];

    public string $basepath;

    public string $version;

    public string $baseNamespace = "SolutionForest\OcppPhp\Ocpp\\";

    public function __construct(?string $version = null)
    {
        if ($version !== null) {
            $this->setVersion($version);
        }
    }

    /**
     * @param  TClasses  $classes
     */
    abstract protected function processClasses(array $classes): void;

    /**
     * @return TClasses
     */
    abstract protected function parseSchema(string $schema): array;

    /**
     * Find every inline enum in a message schema (definitions are not walked: OCPP schemas
     * only use named "...EnumType" definitions there).
     *
     * @param  array<mixed>  $schema
     * @return list<array{path: string, values: list<string>, enum: ?string}>
     */
    public static function findInlineEnums(string $version, string $schemaName, array $schema): array
    {
        $found = [];

        self::walkProperties($schema, function (array $node, string $path) use (&$found, $version, $schemaName): void {
            if (is_array($node['enum'] ?? null)) {
                $found[] = [
                    'path' => $path,
                    'values' => self::stringList($node['enum']),
                    'enum' => self::resolveInlineEnum($version, $schemaName, $path),
                ];
            }
        });

        return $found;
    }

    /**
     * Find every inline object (an object with properties, not a $ref) in a message schema.
     *
     * @param  array<mixed>  $schema
     * @return list<array{path: string, node: array<mixed>, name: ?string}>
     */
    public static function findInlineObjects(string $version, string $schemaName, array $schema): array
    {
        $found = [];

        self::walkProperties($schema, function (array $node, string $path) use (&$found, $version, $schemaName): void {
            if (isset($node['properties'])) {
                $found[] = [
                    'path' => $path,
                    'node' => $node,
                    'name' => self::resolveInline(self::INLINE_OBJECTS[$version] ?? [], $schemaName, $path),
                ];
            }
        });

        return $found;
    }

    public static function resolveInlineEnum(string $version, string $schemaName, string $path): ?string
    {
        return self::resolveInline(self::INLINE_ENUMS[$version] ?? [], $schemaName, $path);
    }

    public function main(): void
    {
        $this->readVersion();

        foreach ($this->schemaFiles() as $schema) {
            $classes = $this->parseSchema($schema);
            $this->processClasses($classes);
        }
    }

    public function setVersion(string $version): void
    {
        if (! in_array($version, self::SUPPORTED_VERSIONS, true)) {
            echo "Invalid version selected. Exiting.\n";
            exit(1);
        }

        $this->basepath = '/../Ocpp/'.$version;
        $this->version = $version;
    }

    /**
     * A decoded JSON value as an array (a schema node); anything else is an empty node.
     *
     * @return array<mixed>
     */
    protected static function node(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    protected static function text(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    /**
     * The strings of a decoded JSON array, e.g. the values of an "enum".
     *
     * @return list<string>
     */
    protected static function stringList(mixed $value): array
    {
        return array_values(array_filter(self::node($value), is_string(...)));
    }

    /**
     * @return array<mixed>
     */
    protected static function readSchema(string $path): array
    {
        return self::node(json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @return list<string>
     */
    protected function schemaFiles(): array
    {
        $files = glob(__DIR__.$this->basepath.'/Schemas/*.json') ?: [];
        sort($files);

        return $files;
    }

    protected function readVersion(): void
    {
        if (isset($this->version)) {
            return;
        }

        echo 'Select version ('.implode('/', self::SUPPORTED_VERSIONS).'): ';
        $this->setVersion(trim((string) fgets(STDIN)));
    }

    protected function generateDataClass(ClassType|EnumType|InterfaceType $class, string $filepath): void
    {
        $subnamespace = ucfirst($filepath);
        $namespace = new PhpNamespace($this->baseNamespace.$this->version.'\\'.$subnamespace);

        if ($class instanceof ClassType) {
            $namespace = $this->handleUseStatements($namespace, $class);
        }

        $filename = $class->getName();
        $printer = new PsrPrinter;
        $printer->setTypeResolving(false);

        $class = $printer->printClass($class, $namespace);
        $namespace = $printer->printNamespace($namespace);

        echo "Creating {$filename} in {$subnamespace}\n";

        $directory = __DIR__."{$this->basepath}/{$filepath}";
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents("{$directory}/{$filename}.php", "<?php\n\ndeclare(strict_types=1);\n\n{$namespace}{$class}");
    }

    protected function getNameFromSchema(string $schemaPath): string
    {
        $title = basename($schemaPath, '.json');

        return preg_replace('/[^a-zA-Z0-9]+/', '', $title) ?? $title;
    }

    /**
     * @param  array<mixed>  $propertyArray  An object schema (with "properties" and "required").
     * @param  array<mixed>  $schemaContent  The whole schema file, for its "definitions".
     * @param  string|null  $schemaName  Schema file name (e.g. "ResetResponse"), used to resolve
     *                                   inline enums and inline objects (core OCPP 1.6).
     * @param  string  $pathPrefix  Property path of this object inside the schema ('' at the root).
     */
    protected function mapPropertiesToClass(array $propertyArray, string $className, array $schemaContent, ?string $schemaName = null, string $pathPrefix = ''): ClassType
    {
        $class = new ClassType($className);
        $requiredProperties = self::node($propertyArray['required'] ?? null);

        foreach (self::node($propertyArray['properties'] ?? null) as $property => $definition) {
            $property = (string) $property;
            $required = in_array($property, $requiredProperties, true);
            $path = $pathPrefix === '' ? $property : "{$pathPrefix}.{$property}";
            $resolved = $this->resolvePropertyType(self::node($definition), $schemaContent, $schemaName, $path);

            if ($resolved['datatype'] !== null) {
                $phpType = ($required ? '' : '?').$resolved['datatype'];
            } elseif ($resolved['enum'] !== null) {
                $phpType = ($required ? '' : 'null|').'string|'.$resolved['enum'];
            } else {
                $phpType = $this->mapSchemaTypeToPhp($resolved['type'], $required);
            }

            $classProperty = $class->addProperty($property)
                ->setType($phpType)
                ->addComment(trim(html_entity_decode($resolved['description'])));

            if ($resolved['itemType'] !== null) {
                // Class names are replaced with imported short names by handleUseStatements().
                $classProperty->addComment("@var list<{$resolved['itemType']}>".($required ? '' : '|null'));
            }

            if ($resolved['listOf'] !== null) {
                // Resolved to a short name by handleUseStatements().
                $classProperty->addAttribute(ListOf::class, [new Literal($resolved['listOf'].'::class')]);
            }

            // Optional properties default to null, so reading an absent field returns null
            // instead of throwing "must not be accessed before initialization".
            // Required properties stay uninitialized until set.
            if (! $required && $phpType !== null) {
                $classProperty->setValue(null);
            }
        }

        return $class;
    }

    /**
     * A definition generated as a Datatype class: an object with properties.
     * CustomDataType stays a free-form object (it allows additional properties).
     *
     * @param  array<mixed>  $definition
     */
    protected function isDatatypeDefinition(string $name, array $definition): bool
    {
        return ! str_contains($name, 'EnumType')
            && $name !== 'CustomDataType'
            && isset($definition['properties']);
    }

    /**
     * Replace fully qualified class names in property types and #[ListOf] attributes with
     * imported short names. An import that would clash with the class itself or another
     * import gets a suffix (e.g. class Reset using enum Reset imports it as ResetEnum).
     */
    protected function handleUseStatements(PhpNamespace $namespace, ClassType $class): PhpNamespace
    {
        $aliases = [];
        $import = function (string $fqcn) use ($namespace, $class, &$aliases): string {
            $position = (int) strrpos($fqcn, '\\');
            $short = substr($fqcn, $position + 1);

            if (substr($fqcn, 0, $position) === $namespace->getName()) {
                return $short;
            }

            if (isset($aliases[$fqcn])) {
                return $aliases[$fqcn];
            }

            $alias = $short;
            if ($alias === $class->getName() || in_array($alias, $aliases, true)) {
                $alias .= str_contains($fqcn, '\\Enums\\') ? 'Enum' : 'Datatype';
            }

            $namespace->addUse($fqcn, $alias);

            return $aliases[$fqcn] = $alias;
        };

        foreach ($class->getProperties() as $property) {
            $type = (string) $property->getType();
            if (str_contains($type, '\\')) {
                $parts = array_map(
                    static fn (string $part): string => str_contains($part, '\\') ? $import($part) : $part,
                    explode('|', $type),
                );
                $property->setType(implode('|', $parts));
            }

            foreach ($property->getAttributes() as $attribute) {
                $argument = $attribute->getArguments()[0] ?? null;
                if ($attribute->getName() !== ListOf::class || ! $argument instanceof Literal) {
                    continue;
                }

                $itemClass = $import(substr((string) $argument, 0, -strlen('::class')));
                $property->setAttributes([]);
                $property->addAttribute($import(ListOf::class), [new Literal($itemClass.'::class')]);
            }

            // Class names in PHPDoc types (e.g. "@var list<string|...\Enums\Phase>") use the imports too.
            $comment = $property->getComment();
            if ($comment !== null && str_contains($comment, $this->baseNamespace)) {
                $property->setComment(preg_replace_callback(
                    '/'.preg_quote($this->baseNamespace, '/').'[A-Za-z0-9_\\\\]+/',
                    static fn (array $match): string => $import($match[0]),
                    $comment,
                ));
            }
        }

        $parents = [
            'Call' => Call::class,
            'CallResult' => CallResult::class,
            'Send' => Send::class,
            'Datatype' => Datatype::class,
        ];
        $parent = $parents[$class->getExtends() ?? ''] ?? null;
        if ($parent !== null) {
            $namespace->addUse($parent);
        }

        return $namespace;
    }

    protected function mapSchemaTypeToPhp(?string $type, bool $required): ?string
    {
        if ($type === null || $type === '') {
            return null;
        }

        $map = [
            'object' => 'object',
            'array' => 'array',
            'integer' => 'int',
            'string' => 'string',
            'number' => 'float',
            'boolean' => 'bool',
            'any' => 'mixed',
        ];

        $type = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $type) ?? $type);
        $type = $map[$type] ?? 'mixed';

        return $required ? $type : '?'.$type;
    }

    /**
     * Visit every property node below the schema root, depth first.
     * Path segments are property names; array items are traversed implicitly.
     *
     * @param  array<mixed>  $schema
     * @param  callable(array<mixed>, string): void  $visit
     */
    private static function walkProperties(array $schema, callable $visit): void
    {
        $walk = function (array $node, string $path) use (&$walk, $visit): void {
            if ($path !== '') {
                $visit($node, $path);
            }

            foreach (self::node($node['properties'] ?? null) as $name => $property) {
                if (is_array($property)) {
                    $walk($property, $path === '' ? (string) $name : "{$path}.{$name}");
                }
            }

            if (is_array($node['items'] ?? null)) {
                $walk($node['items'], $path);
            }
        };

        $walk($schema, '');
    }

    /**
     * @param  array<string, string>  $map
     */
    private static function resolveInline(array $map, string $schemaName, string $path): ?string
    {
        if (isset($map["{$schemaName}:{$path}"])) {
            return $map["{$schemaName}:{$path}"];
        }

        $segments = explode('.', $path);
        while ($segments !== []) {
            $key = implode('.', $segments);
            if (isset($map[$key])) {
                return $map[$key];
            }

            array_shift($segments);
        }

        return null;
    }

    /**
     * How a property is typed: its JSON schema "type", or a fully qualified enum/Datatype class
     * name; listOf is the Datatype class of the items of an array and itemType the PHPDoc type
     * of the items (both may contain fully qualified class names).
     *
     * @param  array<mixed>  $definition
     * @param  array<mixed>  $schemaContent
     * @return ResolvedType
     */
    private function resolvePropertyType(array $definition, array $schemaContent, ?string $schemaName, string $path): array
    {
        $definitions = self::node($schemaContent['definitions'] ?? null);
        $ref = self::text($definition['$ref'] ?? null);

        if ($ref !== null) {
            $ref = $this->refName($ref);
            $target = self::node($definitions[$ref] ?? null);

            return [
                'type' => self::text($target['type'] ?? null),
                'enum' => str_contains($ref, 'EnumType') ? $this->classIn('Enums', str_replace('EnumType', '', $ref)) : null,
                'datatype' => $this->isDatatypeDefinition($ref, $target) ? $this->classIn('Datatypes', $ref) : null,
                'listOf' => null,
                'itemType' => null,
                'description' => self::text($target['description'] ?? null) ?? '',
            ];
        }

        $type = self::text($definition['type'] ?? null);
        if ($type === null) {
            return ['type' => 'any', 'enum' => null, 'datatype' => null, 'listOf' => null, 'itemType' => null, 'description' => ''];
        }

        $resolved = [
            'type' => $type,
            'enum' => null,
            'datatype' => null,
            'listOf' => null,
            'itemType' => null,
            'description' => self::text($definition['description'] ?? null) ?? '',
        ];

        if ($type === 'array') {
            $items = self::node($definition['items'] ?? null);
            $itemRef = self::text($items['$ref'] ?? null);
            if ($itemRef !== null) {
                $itemRef = $this->refName($itemRef);
                $target = self::node($definitions[$itemRef] ?? null);
                if (str_contains($itemRef, 'EnumType')) {
                    $resolved['itemType'] = 'string|'.$this->classIn('Enums', str_replace('EnumType', '', $itemRef));
                } elseif ($this->isDatatypeDefinition($itemRef, $target)) {
                    $resolved['listOf'] = $this->classIn('Datatypes', $itemRef);
                    $resolved['itemType'] = $resolved['listOf'];
                } else {
                    $resolved['itemType'] = $this->phpDocItemType(self::text($target['type'] ?? null));
                }
            } elseif (isset($items['properties'])) {
                $resolved['listOf'] = $this->classIn('Datatypes', $this->inlineObjectName($schemaName, $path));
                $resolved['itemType'] = $resolved['listOf'];
            } elseif (isset($items['enum']) && $schemaName !== null && ($inlineEnum = self::resolveInlineEnum($this->version, $schemaName, $path)) !== null) {
                $resolved['itemType'] = 'string|'.$this->classIn('Enums', $inlineEnum);
            } else {
                $resolved['itemType'] = $this->phpDocItemType(self::text($items['type'] ?? null));
            }
        } elseif (isset($definition['properties'])) {
            $resolved['datatype'] = $this->classIn('Datatypes', $this->inlineObjectName($schemaName, $path));
        } elseif ($schemaName !== null && isset($definition['enum'])) {
            // Inline enum (core OCPP 1.6): typed with the named enum, like a $ref EnumType.
            $inlineEnum = self::resolveInlineEnum($this->version, $schemaName, $path);
            if ($inlineEnum !== null) {
                $resolved['enum'] = $this->classIn('Enums', $inlineEnum);
            }
        }

        return $resolved;
    }

    private function inlineObjectName(?string $schemaName, string $path): string
    {
        $name = $schemaName === null
            ? null
            : self::resolveInline(self::INLINE_OBJECTS[$this->version] ?? [], $schemaName, $path);

        if ($name === null) {
            throw new RuntimeException(
                "Unmapped inline object {$schemaName}:{$path} in {$this->version}. "
                .'Add its name to SchemaProcessor::INLINE_OBJECTS.'
            );
        }

        return $name;
    }

    private function refName(string $ref): string
    {
        $parts = explode('/', $ref);

        return end($parts);
    }

    private function classIn(string $subnamespace, string $name): string
    {
        return $this->baseNamespace.$this->version.'\\'.$subnamespace.'\\'.$name;
    }

    /**
     * PHPDoc type of a scalar array item; arrays decoded from JSON keep plain values.
     */
    private function phpDocItemType(?string $jsonType): string
    {
        return match ($jsonType) {
            'string' => 'string',
            'integer' => 'int',
            'number' => 'float',
            'boolean' => 'bool',
            default => 'mixed',
        };
    }
}
