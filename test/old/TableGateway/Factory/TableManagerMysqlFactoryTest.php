<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\old\TableGateway\Factory;

use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use rollun\datastore\TableGateway\TableManagerMysql;
use Laminas\Db\Adapter\Adapter;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-01-29 at 18:23:51.
 */
class TableManagerMysqlFactoryTest extends TestCase
{
    /**
     * @var AbstractFactoryInterface
     */
    protected $object;

    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->container = include './config/container.php';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {

    }

    public function testTableGatewayAbstractFactory__canCreateIfTableAbsent()
    {
        $this->object = $this->container->get('TableManagerMysql');
        $this->assertSame(
            get_class($this->object), TableManagerMysql::class
        );
    }
}
