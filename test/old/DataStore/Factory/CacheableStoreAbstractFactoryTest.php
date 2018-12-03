<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\old\DataStore\Factory;

use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class CacheableStoreAbstractFactoryTest extends TestCase
{
    /**
     * @var AbstractFactoryInterface
     */
    protected $object;

    public function testDataStoreMemory__invoke()
    {
        $container = include 'config/container.php';
        $this->object = $container->get('testCacheable');
        $this->assertSame(
            get_class($returnedResponse = $this->object), 'rollun\datastore\DataStore\Cacheable'
        );
    }
}
