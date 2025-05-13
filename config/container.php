<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

/*use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;*/

// Load configuration
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;

$config = require __DIR__ . '/config.php';

// Build container
$container = new ServiceManager();
(new Config($config['dependencies']))->configureServiceManager($container);

// Inject config
$container->setService('config', $config);

return $container;
