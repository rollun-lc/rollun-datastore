<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace rollun\test\functional\DataStore\DataStore;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;
use Zend\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\Factory\DbTableAbstractFactory;
use rollun\datastore\TableGateway\TableManagerMysql;

class DbTableIdentifierCaseTest extends TestCase
{
    private TableManagerMysql $tableManager;

    private TableGateway $tableGateway;

    private AdapterInterface $adapter;

    private string $tableName = 'testTablePkCase';

    protected function setUp(): void
    {
        /** @var \Psr\Container\ContainerInterface $container */
        $container = include './config/container.php';
        $this->adapter = $container->get('db');
        $this->tableManager = new TableManagerMysql($this->adapter);

        if ($this->tableManager->hasTable($this->tableName)) {
            $this->tableManager->deleteTable($this->tableName);
        }

        $this->tableManager->createTable($this->tableName, [
            'Id' => [
                TableManagerMysql::FIELD_TYPE => TableManagerMysql::TYPE_INTEGER,
                TableManagerMysql::PRIMARY_KEY => true,
            ],
            'name' => [
                TableManagerMysql::FIELD_TYPE => TableManagerMysql::TYPE_VARCHAR,
                TableManagerMysql::FIELD_PARAMS => [
                    TableManagerMysql::PROPERTY_LENGTH => 255,
                ],
            ],
            'surname' => [
                TableManagerMysql::FIELD_TYPE => TableManagerMysql::TYPE_VARCHAR,
                TableManagerMysql::FIELD_PARAMS => [
                    TableManagerMysql::PROPERTY_LENGTH => 255,
                ],
            ],
        ]);

        $this->tableGateway = new TableGateway($this->tableName, $this->adapter);
    }

    protected function tearDown(): void
    {
        if ($this->tableManager->hasTable($this->tableName)) {
            $this->tableManager->deleteTable($this->tableName);
        }
    }

    public function testUpdateFailsWithoutConfiguredIdentifierWhenPkIsUppercase(): void
    {
        $this->tableGateway->insert(['Id' => 1, 'name' => 'name', 'surname' => 'surname']);
        $dataStore = new DbTable($this->tableGateway);

        $this->expectException(DataStoreException::class);
        $this->expectExceptionMessage('Item must has primary key');

        $dataStore->update(['Id' => 1, 'name' => 'updated']);
    }

    public function testUpdateSucceedsWhenIdentifierFromConfigMatchesColumnCase(): void
    {
        $this->tableGateway->insert(['Id' => 1, 'name' => 'name', 'surname' => 'surname']);
        $dataStore = new DbTable($this->tableGateway, false, 'Id');

        $updated = $dataStore->update(['Id' => 1, 'name' => 'updated']);

        $this->assertSame(
            ['Id' => 1, 'name' => 'updated', 'surname' => 'surname'],
            $updated
        );
    }

    public function testFactoryInjectsIdentifierFromConfig(): void
    {
        $this->tableGateway->insert(['Id' => 1, 'name' => 'name', 'surname' => 'surname']);

        $config = [
            'dataStore' => [
                'upperPkStore' => [
                    'class' => DbTable::class,
                    'tableName' => $this->tableName,
                    'identifier' => 'Id',
                ],
            ],
        ];

        $container = new ServiceManager([
            'services' => [
                'config' => $config,
                'db' => $this->adapter,
                LoggerInterface::class => new NullLogger(),
            ],
        ]);

        $factory = new DbTableAbstractFactory();

        $this->assertTrue($factory->canCreate($container, 'upperPkStore'));

        /** @var DbTable $dataStore */
        $dataStore = $factory($container, 'upperPkStore');

        $this->assertSame('Id', $dataStore->getIdentifier());
        $this->assertSame(
            ['Id' => 1, 'name' => 'name', 'surname' => 'surname'],
            $dataStore->read(1)
        );
    }
}
