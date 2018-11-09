<?php
global $argv;

error_reporting(E_ALL | E_STRICT);

// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));

if (getenv("APP_ENV") != 'dev') {
    echo "You cannot start test if environment var APP_ENV not set in dev!";
    exit(1);
}

$container = require 'config/container.php';
\rollun\dic\InsideConstruct::setContainer($container);
