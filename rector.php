<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/test',
    ])
    ->withSkip([
        __DIR__ . '/test/old/*',
        OptionalParametersAfterRequiredRector::class => [
            __DIR__ . '/src/Repository/src/ModelRepository.php',
            __DIR__ . '/test/unit/Repository/ModelRepositoryTest.php',
        ],
    ])
    ->withPhpSets(php80: true)
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
