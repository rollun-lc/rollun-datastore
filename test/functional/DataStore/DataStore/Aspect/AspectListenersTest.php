<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\DataStore\Aspect;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use rollun\datastore\DataStore\Aspect\AspectWithEventManagerAbstract;
use rollun\datastore\DataStore\Memory;

/**
 * Class EventManagerTest
 *
 * @author Roman Ratsun <r.ratsun.rollun@gmail.com>
 */
class AspectListenersTest extends TestCase
{
    /**
     * Is attached listener called ?
     */
    public function testIsListenerCalled()
    {
        $row = ['id' => 1, 'name' => 'name 1'];
        $aspect = $this->getContainer()->get('testDataStoreAspect1');
        $aspect->create($row);
        $aspect->update($row);

        if (file_exists('test_on_post_create.json')) {
            unlink('test_on_post_create.json');
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }
    }

    /**
     * Is attached listener didn't called ?
     */
    public function testIsListenerDidNotCalled()
    {
        $row = ['id' => 3, 'name' => 'name 3'];
        $aspect = $this->getContainer()->get('testDataStoreAspect1');
        $aspect->create($row);

        if (!file_exists('test_on_post_create.json')) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        global $container;

        return $container;
    }
}
