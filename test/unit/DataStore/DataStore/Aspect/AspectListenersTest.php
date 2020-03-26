<?php

namespace rollun\test\unit\DataStore\DataStore\Aspect;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\Aspect\AspectAbstract;
use rollun\datastore\DataStore\Factory\DataStoreEventManagerFactory;
use rollun\datastore\DataStore\Memory;

/**
 * Class EventManagerTest
 *
 * @author Roman Ratsun <r.ratsun.rollun@gmail.com>
 */
class AspectListenersTest extends TestCase
{
    /**
     * Test for onPreCreateLister
     */
    public function testOnPreCreateListener()
    {
        $dataStoreName = 'testDataStore';

        $row = ['id' => 1, 'name' => 'name 1'];

        $aspect = new AspectAbstract(new Memory(['id', 'name']), $dataStoreName);
        $aspect->getEventManager()->attach(DataStoreEventManagerFactory::EVENT_KEY . ".$dataStoreName.onPreCreate", function ($event) use ($row) {
            $this->assertEquals($row, $event->getParam('itemData'));
        });

        $aspect->create($row);
    }
}
