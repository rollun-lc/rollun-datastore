<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use rollun\datastore\DataStore\ConditionBuilder\SqlConditionBuilder;
use rollun\datastore\Rql\RqlQuery;
use rollun\datastore\TableGateway\DbSql\MultiInsertSql;
use rollun\datastore\TableGateway\SqlQueryBuilder;
use rollun\dic\InsideConstruct;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\InNode;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

/**
 * Datastore as db table
 *
 * Class DbTable
 * @package rollun\datastore\DataStore
 */
class DbTable extends DataStoreAbstract
{
    const LOG_METHOD = 'method';
    const LOG_TABLE = 'table';
    const LOG_TIME = 'time';
    const LOG_REQUEST = 'request';
    const LOG_RESPONSE = 'response';
    const LOG_ROLLBACK = 'rollbackTransaction';
    const LOG_SQL = 'sql';
    const LOG_COUNT = 'count';

    /**
     * @var TableGateway
     */
    protected $dbTable;

    /**
     * @var SqlQueryBuilder
     */
    protected $sqlQueryBuilder;

    /**
     * @var bool
     */
    protected $writeLogs;

    /**
     * @var LoggerInterface
     */
    protected $loggerService;

    /**
     * DbTable constructor.
     * @param TableGateway $dbTable
     */
    public function __construct(
        TableGateway $dbTable,
        bool $writeLogs = false,
        LoggerInterface $loggerService = null
    ) {
        $this->dbTable = $dbTable;
        $this->writeLogs = $writeLogs;
        InsideConstruct::init([
            'loggerService' => LoggerInterface::class,
        ]);
    }

    protected function getSqlQueryBuilder()
    {
        if ($this->sqlQueryBuilder == null) {
            $this->sqlQueryBuilder = new SqlQueryBuilder($this->dbTable->getAdapter(), $this->dbTable->table);
        }

        return $this->sqlQueryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        if ($rewriteIfExist) {
            // 'rewriteIfExist' do not work with multiply insert
            trigger_error("Option 'rewriteIfExist' is no more use", E_USER_DEPRECATED);
        }

        $adapter = $this->dbTable->getAdapter();
        $adapter->getDriver()->getConnection()->beginTransaction();

        try {
            $insertedItem = $this->insertItem($itemData, $rewriteIfExist);
            $adapter->getDriver()->getConnection()->commit();
        } catch (\Throwable $e) {
            $adapter->getDriver()->getConnection()->rollback();
            $logContext = [
                self::LOG_METHOD => __METHOD__,
                self::LOG_TABLE => $this->dbTable->getTable(),
                self::LOG_REQUEST => $itemData,
                self::LOG_ROLLBACK => true,
                'exception' => $e,
            ];
            $this->writeLogsIfNeeded($logContext, "Request to db table '{$this->dbTable->getTable()}' failed");
            throw new DataStoreException("[{$this->dbTable->getTable()}]Can't insert item. {$e->getMessage()}", 0, $e);
        }

        return $insertedItem;
    }

    /**
     * @param $itemData
     * @param bool $rewriteIfExist
     * @return array|mixed|null
     */
    protected function insertItem($itemData, $rewriteIfExist = false)
    {
        if ($rewriteIfExist && isset($itemData[$this->getIdentifier()])) {
            $this->delete($itemData[$this->getIdentifier()]);
        }

        $start = microtime(true);
        $response = $this->dbTable->insert($itemData);
        $end = microtime(true);

        $logContext = [
            self::LOG_METHOD => 'insert',
            self::LOG_TABLE => $this->dbTable->getTable(),
            self::LOG_REQUEST => $itemData,
            self::LOG_TIME => $this->getRequestTime($start, $end),
            self::LOG_RESPONSE => $response,
        ];

        $this->writeLogsIfNeeded($logContext);

        if (isset($itemData[$this->getIdentifier()])) {
            $insertedItem = $this->read($itemData[$this->getIdentifier()]);
        } else {
            trigger_error("Autoincrement 'id' is not allowed", E_USER_DEPRECATED);
            $id = $this->dbTable->getLastInsertValue();
            $insertedItem = $this->read($id);
        }

        return $insertedItem;
    }

    /**
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {
        if ($createIfAbsent) {
            trigger_error("Option 'createIfAbsent' is no more use.", E_USER_DEPRECATED);
        }

        if (!isset($itemData[$this->getIdentifier()])) {
            throw new DataStoreException('Item must has primary key');
        }

        $adapter = $this->dbTable->getAdapter();
        $adapter->getDriver()->getConnection()->beginTransaction();

        try {
            $result = $this->updateItem($itemData, $createIfAbsent);
            $adapter->getDriver()->getConnection()->commit();
        } catch (\Throwable $e) {
            $adapter->getDriver()->getConnection()->rollback();
            $logContext = [
                self::LOG_METHOD => __METHOD__,
                self::LOG_TABLE => $this->dbTable->getTable(),
                self::LOG_REQUEST => $itemData,
                self::LOG_ROLLBACK => true,
                'exception' => $e,
            ];
            $this->writeLogsIfNeeded($logContext, "Request to db table '{$this->dbTable->getTable()}' failed");
            throw new DataStoreException("[{$this->dbTable->getTable()}]Can't update item. {$e->getMessage()}", 0, $e);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param Query $query
     * @return array
     * @throws DataStoreException
     */
    public function queriedDelete(Query $query)
    {
        if ($query->getLimit()
            || $query->getSort()
            || ($query instanceof RqlQuery && $query->getGroupBy())
            || $query->getSelect()) {
            throw new InvalidArgumentException('Only where clause allowed for delete');
        }

        $conditionBuilder = new SqlConditionBuilder(
            $this->getDbTable()->getAdapter(),
            $this->getDbTable()->getTable()
        );

        $sql = new Sql($this->getDbTable()->getAdapter());
        $delete = $sql->delete($this->getDbTable()->getTable());
        $delete->where($conditionBuilder->__invoke($query->getQuery()));
        $sqlString = $sql->buildSqlString($delete);

        $adapter = $this->dbTable->getAdapter();

        $selectQuery = clone $query;
        $selectQuery->setSelect(new SelectNode([$this->getIdentifier()]));
        $shouldDeletedIds = array_map(function ($item) {
            return $item[$this->getIdentifier()];
        }, $this->query($selectQuery));

        try {
            $statement = $adapter->getDriver()->createStatement($sqlString);
            $result = $statement->execute();
        } catch (\Throwable $e) {
            throw new DataStoreException("[{$this->dbTable->getTable()}]Can't delete records using query", 0, $e);
        }

        if (count($shouldDeletedIds) === $result->getAffectedRows()) {
            return $shouldDeletedIds;
        }

        $result = $this->query($query);

        foreach ($result as $record) {
            if (in_array($record[$this->getIdentifier()], $shouldDeletedIds)) {
                unset($shouldDeletedIds[$record[$this->getIdentifier()]]);
            }
        }

        return $shouldDeletedIds;
    }

    /**
     * {@inheritdoc}
     *
     * @param $record
     * @param Query $query
     * @return array
     */
    public function queriedUpdate($record, Query $query)
    {
        if ($query->getLimit()
            || $query->getSort()
            || ($query instanceof RqlQuery && $query->getGroupBy())
            || $query->getSelect()) {
            throw new InvalidArgumentException('Only where clause allowed for update');
        }

        $selectResult = $this->selectForUpdateWithQuery($query);

        if (!$selectResult) {
            return [];
        }

        $conditionBuilder = new SqlConditionBuilder(
            $this->getDbTable()->getAdapter(),
            $this->getDbTable()->getTable()
        );

        // prepare record
        foreach ($record as $k => $v) {
            if ($v === false) {
                $record[$k] = 0;
            } elseif ($v === true) {
                $record[$k] = 1;
            }
        }

        $sql = new Sql($this->getDbTable()->getAdapter());
        $update = $sql->update($this->getDbTable()->getTable());
        $update->where($conditionBuilder->__invoke($query->getQuery()));
        $update->set($record);
        $sqlString = $sql->buildSqlString($update);

        $adapter = $this->dbTable->getAdapter();

        try {
            $statement = $adapter->getDriver()->createStatement($sqlString);
            $updateResult = $statement->execute();
            $updatedIds = [];

            if ($selectResult->getAffectedRows() === $updateResult->getAffectedRows()) {
                /** @noinspection SuspiciousLoopInspection */
                foreach ($selectResult as $record) {
                    $updatedIds[] = $record[$this->getIdentifier()];
                }
            } else {
                $effectedRecords = $this->query($query);
                /** @noinspection SuspiciousLoopInspection */
                foreach ($selectResult as $record) {
                    if ($record !== $effectedRecords[$this->getIdentifier()]) {
                        $updatedIds[] = $effectedRecords[$this->getIdentifier()];
                    }
                }
            }
        } catch (\Throwable $e) {
            throw new DataStoreException("[{$this->dbTable->getTable()}]Can't update records using query", 0, $e);
        }

        return $updatedIds;
    }

    /**
     * @param array $identifiers
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    private function selectForUpdateWithIds(array $identifiers)
    {
        $adapter = $this->dbTable->getAdapter();
        $valTemplate = '';

        foreach ($identifiers as $identifier) {
            $valTemplate .= '?,';
        }

        $valTemplate = trim($valTemplate, ",");
        $sqlString = "SELECT " . Select::SQL_STAR
            . " FROM {$adapter->getPlatform()->quoteIdentifier($this->dbTable->getTable())}"
            . " WHERE {$adapter->getPlatform()->quoteIdentifier($this->getIdentifier())} IN ($valTemplate)"
            . " FOR UPDATE";

        $statement = $adapter->getDriver()->createStatement($sqlString);
        $statement->setParameterContainer(new ParameterContainer($identifiers));

        return $statement->execute();
    }

    /**
     * @param Query $query
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    private function selectForUpdateWithQuery(Query $query)
    {
        $adapter = $this->dbTable->getAdapter();

        $sqlString = $this->getSqlQueryBuilder()->buildSql($query);
        $sqlString .= " FOR UPDATE";

        $statement = $adapter->getDriver()->createStatement($sqlString);

        return $statement->execute();
    }

    /**
     * @param $itemData
     * @param bool $createIfAbsent
     * @return array|mixed|null
     * @throws DataStoreException
     */
    protected function updateItem($itemData, $createIfAbsent = false)
    {
        $identifier = $this->getIdentifier();
        $id = $itemData[$identifier];
        $this->checkIdentifierType($id);

        // Is row with this index exist ?
        $result = $this->selectForUpdateWithIds([$id]);
        $isExist = $result->count();

        $start = microtime(true);

        if (!$isExist && $createIfAbsent) {
            $response = $this->dbTable->insert($itemData);
            $loggedMethod = 'insert';
        } elseif ($isExist) {
            unset($itemData[$identifier]);
            if (empty($itemData)) {
                throw new DataStoreException("Can't update item with id = $id because there was only id in request without other fields");
            }
            $response = $this->dbTable->update($itemData, [$identifier => $id]);
            $loggedMethod = 'update';
        } else {
            throw new DataStoreException("[{$this->dbTable->getTable()}]Can't update item with id = $id");
        }

        $end = microtime(true);

        $logContext = [
            self::LOG_METHOD => $loggedMethod,
            self::LOG_TABLE => $this->dbTable->getTable(),
            self::LOG_REQUEST => $itemData,
            self::LOG_TIME => $this->getRequestTime($start, $end),
            self::LOG_RESPONSE => $response,
        ];

        $this->writeLogsIfNeeded($logContext);

        return $this->read($id);
    }

    /**
     * {@inheritdoc}
     */
    public function query(Query $query)
    {
        $adapter = $this->dbTable->getAdapter();
        $sqlString = $this->getSqlQueryBuilder()->buildSql($query);

        $logContext = [
            self::LOG_METHOD => __FUNCTION__,
            self::LOG_TABLE => $this->dbTable->getTable(),
            self::LOG_SQL => $sqlString,
        ];

        try {
            $statement = $adapter->getDriver()->createStatement($sqlString);
            $start = microtime(true);
            $resultSet = $statement->execute();
            $end = microtime(true);
        } catch (\PDOException $exception) {
            $logContext['exception'] = $exception;
            $this->writeLogsIfNeeded($logContext, "Request to db table '{$this->dbTable->getTable()}' failed");
            throw new DataStoreException(
                "Error by execute '$sqlString' query to {$this->getDbTable()->getTable()}.",
                500,
                $exception
            );
        } catch (\Throwable $e) {
            $logContext['exception'] = $e;
            $this->writeLogsIfNeeded($logContext, "Request to db table '{$this->dbTable->getTable()}' failed");
            throw $e;
        }

        $result = [];

        foreach ($resultSet as $itemData) {
            $result[] = $itemData;
        }

        $logContext[self::LOG_TIME] = $this->getRequestTime($start, $end);
        $logContext[self::LOG_RESPONSE] = array_slice($result, 0, 100);
        $logContext[self::LOG_COUNT] = count($result);

        $this->writeLogsIfNeeded($logContext);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $identifier = $this->getIdentifier();
        $this->checkIdentifierType($id);
        $element = $this->read($id);

        if (!$element) {
            return $element;
        }

        $request = [$identifier => $id];

        $logContext = [
            self::LOG_METHOD => __FUNCTION__,
            self::LOG_TABLE => $this->dbTable->getTable(),
            self::LOG_REQUEST => $request,
        ];

        try {
            $start = microtime(true);
            $response = $this->dbTable->delete($request);
            $end = microtime(true);
        } catch (\Throwable $e) {
            $logContext['exception'] = $e;
            $this->writeLogsIfNeeded($logContext, "Request to db table '{$this->dbTable->getTable()}' failed");
            throw $e;
        }

        $logContext[self::LOG_TIME] = $this->getRequestTime($start, $end);
        $logContext[self::LOG_RESPONSE] = $response;

        $this->writeLogsIfNeeded($logContext);

        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public function read($id)
    {
        $this->checkIdentifierType($id);
        $identifier = $this->getIdentifier();

        $request = [$identifier => $id];

        $logContext = [
            self::LOG_METHOD => __FUNCTION__,
            self::LOG_TABLE => $this->dbTable->getTable(),
            self::LOG_REQUEST => $request,
        ];

        try {
            $start = microtime(true);
            $rowSet = $this->dbTable->select($request);
            $end = microtime(true);
        } catch (\Throwable $e) {
            $logContext['exception'] = $e;
            $this->writeLogsIfNeeded($logContext, "Request to db table '{$this->dbTable->getTable()}' failed");
            throw $e;
        }

        $row = $rowSet->current();
        $response = null;

        if (isset($row)) {
            $response = $row->getArrayCopy();
        }

        $logContext[self::LOG_TIME] = $this->getRequestTime($start, $end);
        $logContext[self::LOG_RESPONSE] = $response;

        $this->writeLogsIfNeeded($logContext);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        $where = '1=1';
        return $this->dbTable->delete($where);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $adapter = $this->dbTable->getAdapter();

        $sql = 'SELECT COUNT(*) AS count FROM ' . $adapter->getPlatform()->quoteIdentifier($this->dbTable->getTable());

        $statement = $adapter->getDriver()->createStatement($sql);
        $result = $statement->execute();

        return $result->current()['count'];
    }

    /**
     * @param array $itemsData
     * @return array|array[]
     * @throws DataStoreException
     */
    public function multiCreate($itemsData)
    {
        $multiInsertTableGw = $this->createMultiInsertTableGw();
        $multiInsertTableGw->getAdapter()->getDriver()->getConnection()->beginTransaction();
        $getIdCallable = function ($item) {
            return $item[$this->getIdentifier()];
        };

        try {
            $identifiers = array_map($getIdCallable, $itemsData);
            $multiInsertTableGw->insert($itemsData);

            $query = new Query();
            $query->setQuery(new InNode($this->getIdentifier(), $identifiers));
            $insertedItems = $this->query($query);
            $multiInsertTableGw->getAdapter()->getDriver()->getConnection()->commit();
        } catch (\Throwable $throwable) {
            $multiInsertTableGw->getAdapter()->getDriver()->getConnection()->rollback();

            throw new DataStoreException(
                "Exception by multi create to table {$this->dbTable->table}. Details: {$throwable->getMessage()}",
                500,
                $throwable
            );
        }

        return array_map($getIdCallable, $insertedItems);
    }

    /**
     * @return TableGateway
     */
    public function getDbTable()
    {
        return $this->dbTable;
    }

    /**
     * {@inheritdoc}
     */
    protected function getKeys()
    {
        $identifier = $this->getIdentifier();
        $select = $this->dbTable->getSql()->select();
        $select->columns([$identifier]);

        $resultSet = $this->dbTable->selectWith($select);
        $keys = [];

        foreach ($resultSet as $key => $result) {
            $keys[] = $key;
        }

        return $keys;
    }

    protected function writeLogsIfNeeded(array $logContext = [], string $message = null)
    {
        if (!$this->writeLogs) {
            return;
        }

        if (is_null($message)) {
            $message = "Request to db table '{$this->dbTable->getTable()}'";
        }

        $this->loggerService->debug($message, $logContext);
    }

    /**
     * @return TableGateway
     */
    private function createMultiInsertTableGw()
    {
        $multiInsertTableGw = new TableGateway(
            $this->dbTable->getTable(),
            $this->dbTable->getAdapter(),
            $this->dbTable->getFeatureSet(),
            $this->dbTable->getResultSetPrototype(),
            new MultiInsertSql($this->dbTable->getAdapter(), $this->dbTable->getTable())
        );

        return $multiInsertTableGw;
    }

    private function getRequestTime(float $start, float $end): float
    {
        return round($end - $start, 3);
    }
}
