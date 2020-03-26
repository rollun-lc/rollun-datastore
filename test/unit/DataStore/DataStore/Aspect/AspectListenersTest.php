<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\DataStore\DataStore\Aspect;

use PHPUnit\Framework\TestCase;
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
        $aspect = new AspectWithEventManagerAbstract(new Memory(['id', 'name']));
        $aspect->getEventManager()->attach('onPreCreate', function ($event) use ($row) {
            $this->assertEquals($row, $event->getParam('itemData'));
        });

        $aspect->create($row);
    }

    /**
     * Is attached listener didn't called ?
     */
    public function testIsListenerDidNotCalled()
    {
        $row = ['id' => 1, 'name' => 'name 1'];
        $aspect = new AspectWithEventManagerAbstract(new Memory(['id', 'name']));
        $aspect->getEventManager()->attach('onPreUpdate', function ($event) use ($row) {
            $this->assertTrue(false);
        });

        $aspect->create($row);

        $this->assertTrue(true);
    }
}
