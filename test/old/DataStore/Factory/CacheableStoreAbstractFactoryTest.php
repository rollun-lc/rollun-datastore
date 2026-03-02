<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\old\DataStore\Factory;

use Laminas\Db\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

class CacheableStoreAbstractFactoryTest extends TestCase
{
    /**
     * @var AbstractFactoryInterface
     */
    protected $object;
    protected $container;

    public function testDataStoreMemory__invoke()
    {
        $this->container = include 'config/container.php';
        $this->object = $this->container->get('testCacheable');
        $this->assertSame(
            get_class($returnedResponse = $this->object), 'rollun\datastore\DataStore\Cacheable'
        );
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->container) && $this->container instanceof ContainerInterface && $this->container->has('db')) {
                $dbAdapter = $this->container->get('db');
                if ($dbAdapter instanceof AdapterInterface) {
                    $dbAdapter->getDriver()->getConnection()->disconnect();
                }
            }
        } catch (\Throwable $e) {
            // Do not hide test assertions with cleanup failures.
        } finally {
            $this->container = null;
            gc_collect_cycles();
        }
    }
}
