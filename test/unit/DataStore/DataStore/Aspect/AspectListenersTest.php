<?php

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
        $aspect->getEventManager()->attach($aspect->createEventName('onPreCreate'), function ($event) use ($row) {
            $this->assertEquals($row, $event->getParam('itemData'));
        });

        $aspect->create($row);
    }
}
