<?php

namespace rollun\test\functional;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use rollun\dic\InsideConstruct;
use Zend\ServiceManager\ServiceManager;

/**
 * У Функциональных тестов APP_ENV = 'test' согласно phpunit.xml
 *
 * @package rollun\test\Functional
 */
class FunctionalTestCase extends PHPUnitTestCase
{
    /**
     * @var ServiceManager|null
     */
    private $container = null;

    protected function getContainer(): ServiceManager
    {
        if ($this->container === null) {
            $this->container = require __DIR__ . '/../../config/container.php';
            InsideConstruct::setContainer($this->container);
        }

        return $this->container;
    }
}
