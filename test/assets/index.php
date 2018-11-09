<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

use rollun\datastore\DataStore\Memory;
use rollun\datastore\Middleware\DataStoreApi;
use Zend\Diactoros\Server;
use Zend\Stratigility\MiddlewarePipe;
use Zend\Stratigility\NoopFinalHandler;

// Delegate static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server'
    && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

chdir(dirname(getcwd() . '/../../../'));

require 'vendor/autoload.php';

/** @var \Zend\ServiceManager\ServiceManager $container */
$container = require 'config/container.php';
\rollun\dic\InsideConstruct::setContainer($container);

$dataStoreService = 'dataStoreService';
$container->setService($dataStoreService, new Memory());

$app = new MiddlewarePipe();
$app->pipe($container->get(DataStoreApi::class));

$server = Server::createServer(
    $app,
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$server->listen(new NoopFinalHandler());
