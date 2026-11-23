<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRector;
use Rector\Php85\Rector\Property\AddOverrideAttributeToOverriddenPropertiesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        // Generated from the JSON schemas by src/Scripts: change the generator instead (CI fails on any drift).
        __DIR__.'/src/Ocpp/v16',
        __DIR__.'/src/Ocpp/v201',
        __DIR__.'/src/Ocpp/v21',
        MakeInheritedMethodVisibilitySameAsParentRector::class,
        AddOverrideAttributeToOverriddenPropertiesRector::class,
        // Public constructors are library API: dropping an unused parameter breaks callers.
        RemoveUnusedConstructorParamRector::class => [
            __DIR__.'/src',
        ],
    ])
    ->withImportNames(
        removeUnusedImports: true,
    )
    ->withCache(
        cacheDirectory: __DIR__.'/tmp/rector',
        cacheClass: FileCacheStorage::class,
    )
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
    )
    // Targets the minimum PHP version from composer.json ("php": "^8.4").
    ->withPhpSets();
