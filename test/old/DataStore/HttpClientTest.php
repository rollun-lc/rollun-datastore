<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\old\DataStore;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Http\\Client;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-01-11 at 16:19:25.
 */
class HttpClientTest extends AbstractTest
{
    /**
     * @var TableGateway
     */
    protected $object;

    /**
     * @var Adapter
     */
    protected $adapter;

    protected $dbTableName;

    protected $configTableDefault = [
        'id' => 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY',
        'anotherId' => 'INT NOT NULL',
        'fString' => 'CHAR(20)',
        'fInt' => 'INT',

    ];

    public function providerHeader()
    {
        return [
            [
                "limit(1)",
                ["Content-Range" => "items 1-1/4"],
                ['With-Content-Range' => '*']
            ],
            [
                "limit(3,1)",
                ["Content-Range" => "items 2-4/4"],
                ['With-Content-Range' => '*']
            ],
            [
                "",
                ["Content-Range" => "items 1-4/4"],
                ['With-Content-Range' => '*']
            ],
        ];
    }

    /**
     * @param $queryString
     * @param $headerExpected
     * @param $headers
     * @dataProvider providerHeader()
     */
    public function test_header($queryString, $headerExpected, $headers)
    {
        $this->_initObject();
        $url = $this->config['testHttpClient']['url'] . "?$queryString";
        $client = new Client($url);
        $headers = array_merge($headers, ['Accept' => 'application/json']);
        $client->setHeaders($headers);
        $client->setOptions(['timeout' => 60]);
        $response = $client->send();
        $headers = $response->getHeaders()->toArray();

        foreach ($headerExpected as $key => $value) {
            $this->assertTrue(isset($headers[$key]));
            $this->assertEquals($value, $headers[$key]);
        }
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
        $dbTable = new TableGateway($this->dbTableName, $this->adapter);

        foreach ($data as $record) {
            $inserted = $dbTable->insert($record);
        }
    }

    /**
     * This method init $this->object
     */
    protected function _prepareTable($data)
    {

        $quoteTableName = $this->adapter->platform->quoteIdentifier($this->dbTableName);

        $deleteStatementStr = "DROP TABLE IF EXISTS " . $quoteTableName;
        $deleteStatement = $this->adapter->query($deleteStatementStr);
        $deleteStatement->execute();

        $createStr = "CREATE TABLE IF NOT EXISTS  " . $quoteTableName;
        $fields = $this->_getDbTableFields($data);
        $createStatementStr = $createStr . '(' . $fields
            . ') ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;';
        $createStatement = $this->adapter->query($createStatementStr);
        $createStatement->execute();
    }

    /**
     * @param $data
     * @return string
     */
    protected function _getDbTableFields($data)
    {
        $record = array_shift($data);
        reset($record);
        $firstKey = key($record);
        $firstValue = array_shift($record);
        $dbTableFields = '';
        if (is_string($firstValue)) {
            $dbTableFields = '`' . $firstKey . '` CHAR(80) PRIMARY KEY';
        } elseif (is_integer($firstValue)) {
            $dbTableFields = '`' . $firstKey . '` INT NOT NULL AUTO_INCREMENT PRIMARY KEY';
        } else {
            trigger_error("Type of primary key must be int or string", E_USER_ERROR);
        }
        foreach ($record as $key => $value) {
            if (is_string($value)) {
                $fieldType = ', `' . $key . '` CHAR(80)';
            } elseif (is_integer($value)) {
                $fieldType = ', `' . $key . '` INT';
            } elseif (is_float($value)) {
                $fieldType = ', `' . $key . '` DOUBLE PRECISION';
            } elseif (is_null($value)) {
                $fieldType = ', `' . $key . '` INT';
            } elseif (is_bool($value)) {
                $fieldType = ', `' . $key . '` BIT';
            } else {
                trigger_error("Type of field of array isn't supported.", E_USER_ERROR);
            }
            $dbTableFields = $dbTableFields . $fieldType;
        }

        return $dbTableFields;
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dbTableName = $this->config['testHttpClient']['tableName'];
        $this->adapter = $this->container->get('db');
        $this->object = $this->container->get('testHttpClient');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        $quoteTableName = $this->adapter->platform->quoteIdentifier($this->dbTableName);
        $deleteStatementStr = "DROP TABLE IF EXISTS " . $quoteTableName;
        $deleteStatement = $this->adapter->query($deleteStatementStr);
        $deleteStatement->execute();
    }
}
