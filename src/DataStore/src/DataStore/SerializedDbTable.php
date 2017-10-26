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

    public function __construct(TableGateway $dbTable = null)
    {
        if(isset($dbTable)){
            parent::__construct($dbTable);
        } else if(isset($this->tableName)) {
            InsideConstruct::setConstructParams(["dbTable" => $this->tableName]);
        } else {
            throw new DataStoreException("dbTable not sent and tableName not exist(from wakeup).");
        }
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
     */
    public function __wakeup()
    {
        $this->__construct(null);
    }
}
