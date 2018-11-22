<?php

use Symfony\Component\Dotenv\Dotenv;
use Zend\ConfigAggregator\ArrayProvider;
use Zend\ConfigAggregator\ConfigAggregator;
use Zend\ConfigAggregator\PhpFileProvider;

// To enable or disable caching, set the `ConfigAggregator::ENABLE_CACHE` boolean in
// `config/autoload/local.php`.
$cacheConfig = [
    'config_cache_path' => 'data/config-cache.php',
];

// Make environment variables stored in .env accessible via getenv(), $_ENV or $_SERVER.
(new Dotenv())->load('.env');

// Determine application environment ('dev' or 'prod').
$appEnv = getenv('APP_ENV');

$aggregator = new ConfigAggregator(
    [
        \Zend\Db\ConfigProvider::class,
        \Zend\Validator\ConfigProvider::class,
        \Zend\Expressive\Router\ConfigProvider::class,

        // Include cache configuration
        new ArrayProvider($cacheConfig),

        // Default App module config
        // Load application config in a pre-defined order in such a way that local settings
        // overwrite global settings. (Loaded as first to last):
        //   - `global.php`
        //   - `*.global.php`
        //   - `local.php`
        //   - `*.local.php`
        new PhpFileProvider('config/autoload/{{,*.}global,{,*.}local}.php'),

        // Load application config according to environment:
        //   - `dev.global.php`,   `test.global.php`,   `prod.global.php`
        //   - `*.dev.global.php`, `*.test.global.php`, `*.prod.global.php`
        //   - `dev.local.php`,    `test.local.php`,     `prod.local.php`
        //   - `*.dev.local.php`,  `*.test.local.php`,  `*.prod.local.php`
        new PhpFileProvider(realpath(__DIR__) . "/autoload/{{,*.}{$appEnv}.global,{,*.}{$appEnv}.local}.php"),

        // Load development config if it exists
        new PhpFileProvider('config/development.config.php'),
    ], $cacheConfig['config_cache_path']
);

return $aggregator->getMergedConfig();
