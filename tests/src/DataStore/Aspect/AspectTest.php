<?php

namespace rolluncom\test\datastore\DataStore\Aspect;

use rolluncom\test\datastore\DataStore\AbstractTest;

class AspectTest extends AbstractTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->object = $this->container->get('testAspectAbstract');
    }

    /**
     * This method init $this->object
     */
    protected function _initObject($data = null)
    {
        if (is_null($data)) {
            $data = $this->_itemsArrayDelault;
        }
        foreach ($data as $record) {
            $this->object->create($record);
        }
    }

}