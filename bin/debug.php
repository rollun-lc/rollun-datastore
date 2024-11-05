<?php

use Laminas\ServiceManager\ServiceManager;
use rollun\dic\InsideConstruct;
use rollun\logger\LifeCycleToken;

error_reporting(E_ALL ^ E_USER_DEPRECATED ^ E_DEPRECATED);

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

/** @var ServiceManager $container */
$container = require 'config/container.php';
InsideConstruct::setContainer($container);

$lifeCycleToken = LifeCycleToken::generateToken();
$container->setService(LifeCycleToken::class, $lifeCycleToken);

//$container->get('datastore1')->queriedUpdate(['active' => false], new RqlQuery());

echo 'Done !';
die();
