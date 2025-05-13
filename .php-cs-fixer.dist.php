<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$config = (new Config())
    ->setFinder(Finder::create()
        ->in(__DIR__)
        ->filter(function (\SplFileInfo $file) {
            // Исключаем любые пути, содержащие шаблон /src/*/src/OpenAPI
            $relativePath = str_replace(__DIR__, '', $file->getRealPath());
            return !preg_match('#/test/old/#', $relativePath);
        })
        )
    ->setCacheFile(__DIR__ . '/data/cache/.php-cs-fixer.cache')
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        '@PER-CS' => true,
        '@PHP82Migration' => true,
    ]);

return $config;