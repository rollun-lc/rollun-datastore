<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\TableGateway;

use InvalidArgumentException;
use Exception;
use RuntimeException;
use Laminas\Db\Adapter;
use Laminas\Db\Adapter\Driver\Mysqli\Mysqli;
use Laminas\Db\Adapter\Driver\Pdo\Pdo;
use Laminas\Db\Metadata\Object\ColumnObject;
use Laminas\Db\Metadata\Object\ConstraintObject;
use Laminas\Db\Metadata\Source;
use Laminas\Db\Metadata\Source\Factory;
use Laminas\Db\Sql;
use Laminas\Db\Sql\Ddl\AlterTable;
use Laminas\Db\Sql\Ddl\Column;
use Laminas\Db\Sql\Ddl\Column\ColumnInterface;
use Laminas\Db\Sql\Ddl\Constraint;
use Laminas\Db\Sql\Ddl\CreateTable;

/**
 * Creates, deletes and rewrites table and gets its info
 *
 * Usage:
s * <code>
 *  $tableData = [
 *      'fieldName1' => [
 *          'field_type' => 'Integer',
 *          'field_params' => [
 *              'length' => 10,
 *              'nullable' => true,
 *              'default' => 'foo'
 *              'decimal' => 5, // int|null
 *              'digits' => 5, // int|null
 *              'options' => [
 *                  'autoincrement' => true,
 *                  'unsigned' => true,
 *                  // ... (look down for more info about valid options)
 *              ]
 *          ],
 *          'field_foreign_key' => [
 *              'referenceTable' => ... ,
 *              'referenceColumn' => ... ,
 *              'onDeleteRule' => null, // 'cascade'
 *              'onUpdateRule' => null, //
 *              'name' => null  // or constraint name
 *          ],
 *          'field_unique_key' => true // or constraint name
 *      ],
 *      'fieldName2' => [
 *          // ...
 *      ]
 *      // ...
 *  ]
 *
 *  $tableManager = new TableManagerMysql($adapter);
 *  $tableManager->createTable('tableName', $tableData);
 * </code>
 *
 * The valid 'options' is:
 *  - unsigned
 *  - zerofill
 *  - identity
 *  - serial
 *  - autoincrement
 *  - comment
 *  - columnformat
 *  - format
 *  - storage
 *
 * Class TableManagerMysql
 * @package rollun\datastore\TableGateway
 */
class TableManagerMysql
{
    // Default config keys
    public const KEY_IN_CONFIG = 'tableManagerMysql';
    public const KEY_TABLES_CONFIGS = 'tablesConfigs';
    public const KEY_AUTOCREATE_TABLES = 'autocreateTables';

    // Config key for each field
    public const FIELD_TYPE = 'field_type';
    public const FIELD_PARAMS = 'field_params';
    public const FOREIGN_KEY = 'field_foreign_key';
    public const UNIQUE_KEY = 'field_unique_key';
    public const PRIMARY_KEY = 'field_primary_key';

    // Column type groups
    public const COLUMN_SIMPLE = 'Column';
    public const COLUMN_LENGTH = 'LengthColumn';
    public const COLUMN_PRECISION = 'PrecisionColumn';

    // Types
    public const TYPE_BIG_INTEGER = 'BigInteger';
    public const TYPE_BOOLEAN = 'Boolean';
    public const TYPE_DATE = 'Date';
    public const TYPE_DATETIME = 'Datetime';
    public const TYPE_INTEGER = 'Integer';
    public const TYPE_TIME = 'Time';
    public const TYPE_TIMESTAMP = 'Timestamp';
    public const TYPE_BINARY = 'Binary';
    public const TYPE_BLOB = 'Blob';
    public const TYPE_CHAR = 'Char';
    public const TYPE_TEXT = 'Text';
    public const TYPE_VARBINARY = 'Varbinary';
    public const TYPE_VARCHAR = 'Varchar';
    public const TYPE_DECIMAL = 'Decimal';
    public const TYPE_FLOAT = 'Float';
    public const TYPE_FLOATING = 'Floating';
    public const TYPE_JSON = 'Json';

    // Column properties
    public const PROPERTY_NULLABLE = 'nullable';
    public const PROPERTY_DEFAULT = 'default';
    public const PROPERTY_LENGTH = 'length';
    public const PROPERTY_DIGITS = 'digits';
    public const PROPERTY_DECIMAL = 'decimal';
    public const PROPERTY_OPTIONS = 'options';

    // Option keys
    public const OPTION_REFERENCE_TABLE = 'referenceTable';
    public const OPTION_REFERENCE_COLUMN = 'referenceColumn';
    public const OPTION_ON_DELETE_RULE = 'onDeleteRule';
    public const OPTION_ON_UPDATE_RULE = 'onUpdateRule';
    public const OPTION_NAME = 'name';

    public const OPTION_AUTOINCREMENT = 'autoincrement';
    public const OPTION_UNSIGNED = 'unsigned';
    public const OPTION_ZEROFILL = 'zerofill';
    public const OPTION_IDENTITY = 'identity';
    public const OPTION_SERIAL = 'serial';
    public const OPTION_COMMENT = 'comment';
    public const OPTION_COLUMNFORMAT = 'columnformat';
    public const OPTION_FORMAT = 'format';
    public const OPTION_STORAGE = 'storage';

    /**
     * Grouped column types by column type group
     *
     * @var array
     */
    protected $fieldClasses = [
        self::COLUMN_SIMPLE => [
            self::TYPE_BIG_INTEGER => Column\BigInteger::class,
            self::TYPE_BOOLEAN => Column\Boolean::class,
            self::TYPE_DATE => Column\Date::class,
            self::TYPE_DATETIME => Column\Datetime::class,
            self::TYPE_INTEGER => Column\Integer::class,
            self::TYPE_JSON => \rollun\datastore\TableGateway\Column\Json::class,
            self::TYPE_TIME => Column\Time::class,
            self::TYPE_TIMESTAMP => Column\Timestamp::class,
        ],
        self::COLUMN_LENGTH => [
            self::TYPE_BINARY => Column\Binary::class,
            self::TYPE_BLOB => Column\Blob::class,
            self::TYPE_CHAR => Column\Char::class,
            self::TYPE_TEXT => Column\Text::class,
            self::TYPE_VARBINARY => Column\Varbinary::class,
            self::TYPE_VARCHAR => Column\Varchar::class,
        ],
        self::COLUMN_PRECISION => [
            self::TYPE_DECIMAL => Column\Decimal::class,
            self::TYPE_FLOAT => Column\Float::class,
            self::TYPE_FLOATING => Column\Floating::class,
        ],
    ];

    /**
     * Grouped column properties by column type group
     *
     * @var array
     */
    protected $parameters = [
        self::COLUMN_SIMPLE => [
            self::PROPERTY_NULLABLE => false,
            self::PROPERTY_DEFAULT => null,
            self::PROPERTY_OPTIONS => [],
        ],
        self::COLUMN_LENGTH => [
            self::PROPERTY_LENGTH => null,
            self::PROPERTY_NULLABLE => false,
            self::PROPERTY_DEFAULT => null,
            self::PROPERTY_OPTIONS => [],
        ],
        self::COLUMN_PRECISION => [
            self::PROPERTY_DIGITS => null,
            self::PROPERTY_DECIMAL => null,
            self::PROPERTY_NULLABLE => false,
            self::PROPERTY_DEFAULT => null,
            self::PROPERTY_OPTIONS => [],
        ],
    ];

    /**
     * @var Adapter\Adapter
     */
    protected $db;

    /**
     * TableManagerMysql constructor.
     * @param Adapter\Adapter $db
     * @param null $config
     * @throws Exception
     */
    public function __construct(Adapter\Adapter $db, /**
     * Global table configs
     */
        protected $config = null)
    {
        $this->db = $db;

        if (!isset($this->config[self::KEY_AUTOCREATE_TABLES])) {
            return;
        }

        $autocreateTables = $this->config[self::KEY_AUTOCREATE_TABLES];

        foreach ($autocreateTables as $tableName => $tableConfig) {
            if (!$this->hasTable($tableName)) {
                $this->create($tableName, $tableConfig);
            }
        }
    }

    /**
     * Preparing method of creating table
     *
     * Checks if the table exists and than if one don't creates the new table
     *
     * @param string $tableName
     * @param string $tableConfig
     * @return mixed
     * @throws RuntimeException
     * @throws Exception
     */
    public function createTable($tableName, $tableConfig = null)
    {
        if ($this->hasTable($tableName)) {
            throw new RuntimeException("Table with name {$tableName} is exist. Use rewriteTable()");
        }

        return $this->create($tableName, $tableConfig);
    }

    /**
     * Rewrites the table.
     *
     * Rewrite == delete existing table + create the new table
     *
     * @param string $tableName
     * @param string $tableConfig
     * @return mixed
     */
    public function rewriteTable($tableName, $tableConfig = null)
    {
        if ($this->hasTable($tableName)) {
            $this->deleteTable($tableName);
        }

        return $this->create($tableName, $tableConfig);
    }

    /**
     * Delete table
     *
     * @param $tableName
     * @return Adapter\Driver\ResultInterface
     */
    public function deleteTable($tableName)
    {
        $deleteStatementStr = "DROP TABLE IF EXISTS {$this->db->platform->quoteIdentifier($tableName)}";
        $deleteStatement = $this->db->query($deleteStatementStr);

        return $deleteStatement->execute();
    }

    /**
     * Builds and gets table info
     *
     * @see http://framework.zend.com/manual/current/en/modules/zend.db.metadata.html
     * @param string $tableName
     * @return string
     */
    public function getTableInfoStr($tableName)
    {
        $metadata = Factory::createSourceFromAdapter($this->db);
        $table = $metadata->getTable($tableName);
        $spaces = (fn($count) => str_repeat(' ', $count));

        $result = "{$spaces(4)}With columns:" . PHP_EOL;

        /** @var ColumnObject $column */
        foreach ($table->getColumns() as $column) {
            $result .= "{$spaces(8)}{$column->getName()} -> {$column->getDataType()}" . PHP_EOL;
        }

        $result .= PHP_EOL;
        $result .= "{$spaces(4)}With constraints:" . PHP_EOL;

        /** @var ConstraintObject $constraint */
        foreach ($metadata->getConstraints($tableName) as $constraint) {
            $result .= "{$spaces(8)}{$constraint->getName()} -> {$constraint->getType()}" . PHP_EOL;

            if (!$constraint->hasColumns()) {
                continue;
            }

            $result .= "{$spaces(12)}column: " . implode(', ', $constraint->getColumns());

            if ($constraint->isForeignKey()) {
                $foreignKeyColumns = [];

                foreach ($constraint->getReferencedColumns() as $referenceColumn) {
                    $foreignKeyColumns[] = "{$constraint->getReferencedTableName()}.{$referenceColumn}";
                }

                $result .= ' => ' . implode(', ', $foreignKeyColumns) . PHP_EOL;
                $result .= "{$spaces(12)}OnDeleteRule: {$constraint->getDeleteRule()}" . PHP_EOL;
                $result .= "{$spaces(12)}OnUpdateRule: {$constraint->getUpdateRule()}" . PHP_EOL;
            } else {
                $result .= PHP_EOL;
            }
        }

        return $result;
    }

    /**
     * Checks if the table exists
     *
     * @param string $tableName
     * @return bool
     */
    public function hasTable($tableName)
    {
        $dbMetadata = Source\Factory::createSourceFromAdapter($this->db);
        $tableNames = $dbMetadata->getTableNames();

        return in_array($tableName, $tableNames);
    }

    /**
     * Checks if the table exists
     *
     * @param string $tableName
     * @return bool
     */
    public function hasView($tableName)
    {
        $dbMetadata = Source\Factory::createSourceFromAdapter($this->db);
        $tableNames = $dbMetadata->getViewNames();

        return in_array($tableName, $tableNames);
    }

    /**
     * Returns the table config
     *
     * @return array|null
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Fetches the table config from common config of all the tables
     *
     * @param $tableConfig
     * @return mixed
     * @throws Exception
     */
    public function getTableConfig($tableConfig)
    {
        if (is_string($tableConfig)) {
            $config = $this->getConfig();

            if (isset($config[self::KEY_TABLES_CONFIGS][$tableConfig])) {
                $tableConfig = $config[self::KEY_TABLES_CONFIGS][$tableConfig];
            } else {
                throw new InvalidArgumentException("Unknown table '{$tableConfig}' in config");
            }
        }

        return $tableConfig;
    }

    /**
     * Creates table by its name and config
     *
     * @param $tableName
     * @param $tableConfig
     * @return Adapter\Driver\StatementInterface|\Laminas\Db\ResultSet\ResultSet
     * @throws Exception
     */
    protected function create($tableName, $tableConfig = null)
    {
        $tableConfig = is_null($tableConfig) ? $tableName : $tableConfig;
        $createTable = $this->createCreateTable($tableName, $this->getTableConfig($tableConfig));
        $sql = $this->getCreateTableSql($createTable);

        return $this->db->query($sql, Adapter\Adapter::QUERY_MODE_EXECUTE);
    }

    /**
     * Create and return instance of CreateTable
     *
     * @param $tableName
     * @param $tableConfig
     * @return CreateTable
     */
    protected function createCreateTable(string $tableName, array $tableConfig): CreateTable
    {
        $createTable = new CreateTable($tableName);
        $primaryKeys = [];

        foreach ($tableConfig as $fieldName => $fieldData) {
            if (isset($fieldData[static::PRIMARY_KEY])) {
                $primaryKeys[] = $fieldName;
            }

            $column = $this->createColumn($fieldData, $fieldName);
            $createTable->addColumn($column);

            if (isset($fieldData[self::UNIQUE_KEY])) {
                $constrain = $this->createUniqueKeyConstraint($fieldData[self::UNIQUE_KEY], $fieldName, $tableName);
                $createTable->addConstraint($constrain);
            }

            if (isset($fieldData[self::FOREIGN_KEY])) {
                $constrain = $this->createForeignKeyConstraint($fieldData[self::FOREIGN_KEY], $fieldName, $tableName);
                $createTable->addConstraint($constrain);
            }
        }

        if (count($primaryKeys)) {
            $createTable->addConstraint(new Constraint\PrimaryKey(...$primaryKeys));
        } else {
            $createTable->addConstraint(new Constraint\PrimaryKey('id'));
        }

        return $createTable;
    }

    protected function getCreateTableSql(CreateTable $createTable): string
    {
        $createTableDecorator = new Sql\Platform\Mysql\Ddl\CreateTableDecorator();
        $mySqlPlatformDbAdapter = new Adapter\Platform\Mysql();

        /** @var Mysqli|Pdo|mysqli|\PDO $driver */
        $driver = $this->db->getDriver();
        $mySqlPlatformDbAdapter->setDriver($driver);
        $sqlCreateTable = $createTableDecorator->setSubject($createTable)
            ->getSqlString($mySqlPlatformDbAdapter);

        return "$sqlCreateTable;" . PHP_EOL;
    }

    /**
     * Create and return instance of ColumnInterface
     *
     * @param array $fieldData
     * @param string $fieldName
     * @return ColumnInterface
     */
    protected function createColumn(array $fieldData, string $fieldName): ColumnInterface
    {
        $fieldType = $fieldData[self::FIELD_TYPE];

        switch (true) {
            case array_key_exists($fieldType, $this->fieldClasses[self::COLUMN_SIMPLE]):
                $defaultFieldParameters = $this->parameters[self::COLUMN_SIMPLE];
                $columnClass = $this->fieldClasses[self::COLUMN_SIMPLE][$fieldType];
                break;
            case array_key_exists($fieldType, $this->fieldClasses[self::COLUMN_LENGTH]):
                $defaultFieldParameters = $this->parameters[self::COLUMN_LENGTH];
                $columnClass = $this->fieldClasses[self::COLUMN_LENGTH][$fieldType];
                break;
            case array_key_exists($fieldType, $this->fieldClasses[self::COLUMN_PRECISION]):
                $defaultFieldParameters = $this->parameters[self::COLUMN_PRECISION];
                $columnClass = $this->fieldClasses[self::COLUMN_PRECISION][$fieldType];
                break;
            default:
                throw new InvalidArgumentException("Unknown field type: {$fieldType}");
        }

        $args = [];

        foreach ($defaultFieldParameters as $key => $value) {
            if ($key === self::PROPERTY_OPTIONS
                && isset($fieldData[self::FIELD_PARAMS][self::PROPERTY_OPTIONS])
                && array_key_exists(self::OPTION_AUTOINCREMENT, $fieldData[self::FIELD_PARAMS][self::PROPERTY_OPTIONS])) {
                trigger_error("Autoincrement field is deprecated", E_USER_DEPRECATED);
            }

            if (isset($fieldData[self::FIELD_PARAMS]) && array_key_exists($key, $fieldData[self::FIELD_PARAMS])) {
                $args[] = $fieldData[self::FIELD_PARAMS][$key];
            } else {
                $args[] = $value;
            }
        }

        array_unshift($args, $fieldName);

        return new $columnClass(...$args);
    }

    /**
     * Create and return instance of Constraint\UniqueKey
     *
     * @param null $constraintOptions
     * @param string $fieldName
     * @param string $tableName
     * @return Constraint\UniqueKey
     */
    protected function createUniqueKeyConstraint(
        $constraintOptions,
        string $fieldName,
        string $tableName
    ): Constraint\UniqueKey {
        $constraintName = is_string($constraintOptions) ? $constraintOptions : "UniqueKey_{$tableName}_{$fieldName}";

        return new Constraint\UniqueKey([$fieldName], $constraintName);
    }

    /**
     * Create and return instance of Constraint\ForeignKey
     *
     * @param array $constraintOptions
     * @param string $fieldName
     * @param string $tableName
     * @return Constraint\ForeignKey
     */
    protected function createForeignKeyConstraint(array $constraintOptions, string $fieldName, string $tableName)
    {
        if (is_string($constraintOptions['name'] ?? null)) {
            $constraintName = $constraintOptions;
        } else {
            $constraintName = "ForeignKey_{$tableName}_{$fieldName}";
        }

        if (empty($constraintOptions['referenceTable'])) {
            throw new InvalidArgumentException("Missing option 'referenceTable' for foreign key constraint");
        }

        if (empty($constraintOptions['referenceColumn'])) {
            throw new InvalidArgumentException("Missing option 'referenceColumn' for foreign key constraint");
        }

        return new Constraint\ForeignKey(
            $constraintName,
            [$fieldName],
            $constraintOptions['referenceTable'],
            $constraintOptions['referenceColumn'],
            $constraintOptions['onDeleteRule'] ?? null,
            $constraintOptions['onUpdateRule'] ?? null
        );
    }

    public function getLinkedTables($tableName)
    {
        $sql = "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = "
            . "'{$this->db->getCurrentSchema()}'" . " AND REFERENCED_TABLE_NAME = '{$tableName}'"
            . " AND CONSTRAINT_NAME <> 'PRIMARY' AND REFERENCED_TABLE_NAME IS NOT NULL";

        $rowSet = $this->db->query($sql, Adapter\Adapter::QUERY_MODE_EXECUTE);

        return $rowSet->toArray();
    }

    public function getColumnsNames($tableName)
    {
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = "
            . "'{$this->db->getCurrentSchema()}'" . " AND TABLE_NAME = '{$tableName}'";

        $resultSet = $this->db->query($sql, Adapter\Adapter::QUERY_MODE_EXECUTE);
        $columnsNames = [];

        foreach ($resultSet->toArray() as $column) {
            $columnsNames[] = $column['COLUMN_NAME'];
        }

        return $columnsNames;
    }
}
