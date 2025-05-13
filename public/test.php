<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

use rollun\datastore\Middleware\DataStoreApi;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\Middleware\ErrorResponseGenerator;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Stratigility\MiddlewarePipe;

error_reporting(E_ALL ^ E_USER_DEPRECATED);

// Delegate static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server'
    && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$request = ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);


/** @var \Laminas\ServiceManager\ServiceManager $container */
$container = require 'config/container.php';
\rollun\dic\InsideConstruct::setContainer($container);

$serverRequestFactory = [ServerRequestFactory::class, 'fromGlobals'];
$errorResponseGenerator = function (Throwable $e) {
    $generator = new ErrorResponseGenerator();
    return $generator($e, new ServerRequest(), new Response());
};

$middlewarePipe = new MiddlewarePipe();
$middlewarePipe->pipe($container->get(DataStoreApi::class));

$runner = new RequestHandlerRunner(
    $middlewarePipe,
    new SapiEmitter(),
    $serverRequestFactory,
    $errorResponseGenerator
);
$runner->run();
