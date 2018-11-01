<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use rollun\dic\InsideConstruct;
use Zend\Db\TableGateway\TableGateway;

class SerializedDbTable extends DbTable
{
    protected $tableName;

    public function __construct(TableGateway $dbTable = null)
    {
        if (isset($dbTable)) {
            parent::__construct($dbTable);
        } elseif (isset($this->tableName)) {
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
