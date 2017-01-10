<?php

// try http://__zaboy-rest/api/rest/index_StoreMiddleware?fNumberOfHours=8&fWeekday=Monday
// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));

require 'vendor/autoload.php';
require_once 'config/env_configurator.php';

use Zend\Diactoros\Server;
use rolluncom\datastore\Pipe\MiddlewarePipeOptions;
use rolluncom\datastore\Pipe\Factory\RestRqlFactory;
use Zend\Stratigility\Middleware\ErrorHandler;
use Zend\Stratigility\Middleware\NotFoundHandler;
use Zend\Stratigility\NoopFinalHandler;

// Define application environment - 'dev' or 'prop'
if (getenv('APP_ENV') === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    $env = 'develop';
}

$container = include 'config/container.php';

$RestRqlFactory = new RestRqlFactory();
$rest = $RestRqlFactory($container, '');

$app = new MiddlewarePipeOptions(['env' => isset($env) ? $env : null]); //['env' => 'develop']
$app->raiseThrowables();
$app->pipe('/api/rest', $rest);

$server = Server::createServer($app, $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
$server->listen();
