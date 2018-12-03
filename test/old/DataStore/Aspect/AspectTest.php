<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\old\DataStore\Aspect;

use rollun\test\old\DataStore\AbstractTest;

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