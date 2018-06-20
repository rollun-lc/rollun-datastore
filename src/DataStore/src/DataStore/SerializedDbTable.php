<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 02.10.17
 * Time: 17:27
 */

namespace rollun\datastore\DataStore;

use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\Traits\IdGeneratorTrait;
use rollun\dic\InsideConstruct;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;

class SerializedDbTable extends DbTable
{

    protected $tableName;

    public function __construct(TableGateway $dbTable)
    {
        parent::__construct($dbTable);
        $this->tableName = $this->dbTable->getTable();
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ["tableName"];
    }

    /**
     *
     * @throws \ReflectionException
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(["dbTable" => $this->tableName]);
    }
}
