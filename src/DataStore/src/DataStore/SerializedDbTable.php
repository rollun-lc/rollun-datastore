<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use rollun\datastore\TableGateway\SqlQueryBuilder;
use rollun\dic\InsideConstruct;
use Zend\Db\TableGateway\TableGateway;

class SerializedDbTable extends DbTable
{
    protected $tableName;

    public function __construct(TableGateway $dbTable, bool $writeLogs = false)
    {
        parent::__construct($dbTable, $writeLogs);
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
     * @throws DataStoreException
     */
    public function __wakeup()
    {
        try {
            InsideConstruct::initWakeup([
                "dbTable" => $this->tableName,
            ]);
        } catch (\Throwable $e) {
            throw new DataStoreException("Can't deserialize itself. Reason: {$e->getMessage()}", 0, $e);
        }
    }
}
