<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use Psr\Log\LoggerInterface;
use rollun\datastore\TableGateway\SqlQueryBuilder;
use rollun\dic\InsideConstruct;
use Laminas\Db\TableGateway\TableGateway;

class SerializedDbTable extends DbTable
{
    protected $tableName;

    public function __construct(
        TableGateway $dbTable,
        bool $writeLogs = false,
        ?string $identifier = null,
        ?LoggerInterface $loggerService = null
    ) {
        parent::__construct($dbTable, $writeLogs, $identifier, $loggerService);
        $this->tableName = $this->dbTable->getTable();
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ["tableName", "writeLogs", "identifier"];
    }

    /**
     * @throws DataStoreException
     */
    public function __wakeup()
    {
        try {
            InsideConstruct::initWakeup([
                "dbTable" => $this->tableName,
                "loggerService" => LoggerInterface::class,
            ]);
        } catch (\Throwable $e) {
            throw new DataStoreException("Can't deserialize itself. Reason: {$e->getMessage()}", 0, $e);
        }
    }
}
