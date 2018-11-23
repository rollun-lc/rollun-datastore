<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\DataStoreTest\DataStore\Factory;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Factory\CacheableAbstractFactory;

class CacheableStoreAbstractFactoryTest extends TestCase
{
    /**
     * @var CacheableAbstractFactory
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
