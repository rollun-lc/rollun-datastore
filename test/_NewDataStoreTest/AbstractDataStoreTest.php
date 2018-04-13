<?php

namespace rollun\test\datastore\DataStore;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;


/**
 * Class AbstractDataStoreTest
 *
 */
abstract class AbstractDataStoreTest extends TestCase
{
    /** @var DataStoresInterface */
    protected $object;

    /** @var ContainerInterface */
    protected $container;

    /**
     * @var array
     */
    static private $INITIAL_CONFIG = [];

    /**
     * @param $testName
     * @return mixed
     */
    protected function getConfigForTest(string $testName) {
        return isset(self::$INITIAL_CONFIG[$testName]) ? self::$INITIAL_CONFIG[$testName] : null;
    }

    /**
     * @param $testName
     * @param $config mixed
     */
    protected function setConfigForTest(string $testName, $config) {
        self::$INITIAL_CONFIG[$testName] = $config;
    }

    /**
     * Return test case name from dataProvider
     * @return string
     */
    final protected function getTestCaseName() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $function = $trace[2]["function"];
        preg_match('/^provide(?<name>[\w]+)Data$/', $function, $match);
        $testCaseName = "test{$match["name"]}";
        return $testCaseName;
    }

    /**
     * AbstractDataStoreTest constructor.
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct(string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->container = require './config/container.php';
    }
}