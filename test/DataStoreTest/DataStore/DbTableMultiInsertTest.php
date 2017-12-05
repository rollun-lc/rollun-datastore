<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 21.07.16
 * Time: 13:56
 */

namespace rollun\test\datastore\DataStore;

use Zend\Db\TableGateway\TableGateway;

class DbTableMultiInsertTest extends DbTableTest
{
    /** @var  TableGateway */
    protected $dbTable;

    protected function setUp()
    {
        parent::setUp();
        $tableGateway = $this->config['testDbTableMultiInsert']['tableGateway'];

        $this->dbTable = $this->container->get($tableGateway);

        $this->dbTableName = $this->dbTable->getTable();

        $this->adapter = $this->container->get('db');
        $this->object = $this->container->get('testDbTableMultiInsert');
    }

    public function testCreate_multiRow_withoutId()
    {
        $this->_initObject();
        $data = [];
        foreach (range(1, 20000) as $i) {
            $data[] = [
                'fFloat' => 1000.01 + $i,
                'fString' => 'Create_withoutId' . $i,
            ];
        }

        $newItems = $this->object->create($data);
        $this->assertEquals('Create_withoutId' . 1 ,$newItems['fString']);
    }

    public function testCreate_multiRow_withId()
    {
        $this->_initObject();
        $data = [];
        foreach (range(5, 20000) as $i) {
            $data[] = [
                $this->object->getIdentifier() => $i,
                'fFloat' => 1000.01 + $i,
                'fString' => 'Create_withoutId' . $i,
            ];
        }

        $newItems = $this->object->create($data);
        $this->assertEquals(20000 ,$newItems[$this->object->getIdentifier()]);
    }

    public function testCreate_multiRow_withRewrite()
    {
        $this->_initObject();
        $data = [];
        foreach (range(1, 20000) as $i) {
            $data[] = [
                $this->object->getIdentifier() => $i,
                'fFloat' => 1000.01 + $i,
                'fString' => 'Create_withoutId' . $i,
            ];
        }

        $newItems = $this->object->create($data, true);
        $this->assertEquals(20000 ,$newItems[$this->object->getIdentifier()]);
    }

    /**
     * This method init $this->object
     */
    protected function _initObject($data = null)
    {

        if (is_null($data)) {
            $data = $this->_itemsArrayDelault;
        }

        $this->_prepareTable($data);

        $this->dbTable->insert($data);
    }
}
