<?php

declare(strict_types=1);

namespace functional\DataStore\DataStore\DbTable;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\Rql\RqlQuery;
use rollun\datastore\TableGateway\TableManagerMysql;
use Zend\Db\Adapter\Profiler\Profiler;
use Zend\Db\TableGateway\TableGateway;

/**
 * Bug summary (eq-on-JSON):
 * For RQL query eq(items,string:%5B%5D) the builder generates SQL like:
 *   WHERE items = '[]'
 * where items is a JSON column. Such "binary" comparison of JSON with text '[]'
 * in MySQL returns FALSE, so empty arrays are not found.
 *
 * Correct comparison options:
 *   - WHERE JSON_TYPE(items) = 'ARRAY' AND JSON_LENGTH(items) = 0
 *   - WHERE items = CAST('[]' AS JSON)
 *
 * The test below reproduces the bug (eq on empty JSON array returns 0 rows),
 * and also contains a case confirming that contains on string field works correctly.
 */
final class EqOnJsonFieldTest extends TestCase
{
    /**
     * @var TableManagerMysql
     */
    private $mysqlManager;
    /**
     * @var TableGateway
     */
    private $tableGateway;
    /**
     * @var DbTable
     */
    private $dataStore;
    /**
     * @var Profiler
     */
    private $profiler;
    /**
     * @var string
     */
    private $tableName = 'orders_json_eq_test';

    /**
     * Table configuration with JSON support
     */
    private function getTableConfig(): array
    {
        return [
            'id' => [
                'field_type' => 'Integer',
                'field_primary_key' => true
            ],
            'purchase_order_number' => [
                'field_type' => 'Varchar',
                'field_params' => [
                    'length' => 255,
                ]
            ],
            'items' => [
                'field_type' => 'Json',
                'field_params' => [
                    'nullable' => true,
                ]
            ]
        ];
    }

    protected function setUp(): void
    {
        $container = $this->getContainer();
        $adapter = $container->get('db');

        // Add profiler to track SQL queries
        $this->profiler = new Profiler();
        $adapter->setProfiler($this->profiler);

        $this->mysqlManager = new TableManagerMysql($adapter);

        if ($this->mysqlManager->hasTable($this->tableName)) {
            $this->mysqlManager->deleteTable($this->tableName);
        }

        $this->mysqlManager->createTable($this->tableName, $this->getTableConfig());

        $this->tableGateway = new TableGateway($this->tableName, $adapter);
        $this->dataStore = new DbTable($this->tableGateway);

        $this->createTestData();
    }

    protected function tearDown(): void
    {
        $this->mysqlManager->deleteTable($this->tableName);
    }

    /**
     * Create test data for JSON field tests
     */
    private function createTestData(): void
    {
        foreach ($this->getTestData() as $row) {
            $this->dataStore->create($row);
        }
    }

    /**
     * Test data provider for JSON field tests
     */
    public function getTestData(): iterable
    {
        // Empty arrays test cases
        yield ['id' => 1, 'purchase_order_number' => 'bulk return', 'items' => '[]'];
        yield ['id' => 2, 'purchase_order_number' => '', 'items' => '[]'];

        // Complex JSON objects
        yield ['id' => 3, 'purchase_order_number' => 'Canceled', 'items' => '[{"csn":"873-0056","rid":"IB99N","unitPrice":64.2,"warehouse":"TX","qtyOrdered":1,"qtyShipped":1,"trackNumbers":[],"qtyBackOrdered":0}]'];
        yield ['id' => 4, 'purchase_order_number' => '637686nm', 'items' => '[{"csn":"03060005","rid":"72D6L","unitPrice":99.3,"warehouse":null,"qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'];
        yield ['id' => 5, 'purchase_order_number' => '644261nm', 'items' => '[{"csn":"03060003","rid":"4WXCA","unitPrice":125.1,"warehouse":null,"qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'];
        yield ['id' => 6, 'purchase_order_number' => '650229nm', 'items' => '[{"csn":"37040193","rid":"MYF9N","unitPrice":50.92,"warehouse":null,"qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'];
        yield ['id' => 7, 'purchase_order_number' => '657824nm', 'items' => '[{"csn":"03410011","rid":"39LDA","unitPrice":19.32,"warehouse":"NC","qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'];
        yield ['id' => 8, 'purchase_order_number' => '635653nm', 'items' => '[{"csn":"234130","rid":"76ZRC","unitPrice":8.9,"warehouse":"2","qtyOrdered":3,"qtyShipped":3,"trackNumbers":["9400136208090275678269"],"qtyBackOrdered":0}]'];
        yield ['id' => 9, 'purchase_order_number' => '635747nm', 'items' => '[{"csn":"987170","rid":"1I3RU","unitPrice":8.04,"warehouse":"2","qtyOrdered":1,"qtyShipped":1,"trackNumbers":["390292096488"],"qtyBackOrdered":0}]'];
        yield ['id' => 10, 'purchase_order_number' => '635994nm', 'items' => '[{"csn":"163274","rid":"A5VRM","unitPrice":25.75,"warehouse":"3","qtyOrdered":1,"qtyShipped":1,"trackNumbers":["390323657983"],"qtyBackOrdered":0}]'];

        // Additional test cases for bugs
        yield ['id' => 11, 'purchase_order_number' => 'json-null', 'items' => 'null']; // JSON literal null
        yield ['id' => 12, 'purchase_order_number' => 'sql-null', 'items' => null];   // SQL NULL
    }

    /**
     * Data provider for JSON query tests
     */
    public function jsonQueryDataProvider(): iterable
    {
        // Test that empty JSON array query uses proper JSON functions after fix
        yield 'empty array' => [
            'query' => 'eq(items,string:%5B%5D)',
            'expectedCount' => 2,
            'expectedSqlPattern' => '/JSON_TYPE\(\s*(?:`?\w+`?\.)?`?items`?\s*\)\s*=\s*\'ARRAY\'/i',
            'description' => 'Empty JSON array should use JSON_TYPE and JSON_LENGTH functions'
        ];

        // Test that JSON null query uses CAST function after fix
        yield 'json null' => [
            'query' => 'eq(items,string:null)',
            'expectedCount' => 1,
            'expectedSqlPattern' => '~(?:`?\w+`?\.)?`?items`?\s*=\s*CAST\(\s*\'null\'\s*AS\s+JSON\)~i',
            'description' => 'JSON null should use CAST function'
        ];

        yield 'empty object' => [
            'query' => 'eq(items,string:%7B%7D)',
            'expectedCount' => 0,
            'expectedSqlPattern' => '/JSON_TYPE\(\s*(?:`?\w+`?\.)?`?items`?\s*\)\s*=\s*\'OBJECT\'/i',
            'description' => 'Empty object should use JSON_TYPE and JSON_LENGTH functions'
        ];
    }

    /**
     * Test JSON field equality queries
     *
     * @dataProvider jsonQueryDataProvider
     */
    public function testJsonFieldEqualityQueries(
        string $query,
        int $expectedCount,
        string $expectedSqlPattern,
        string $description
    ): void {
        $rows = $this->executeQuery($query);

        $this->assertCount($expectedCount, $rows, $description);

        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);
        $this->assertMatchesRegularExpression($expectedSqlPattern, $sql, "SQL should match pattern: {$sql}");
    }

    /**
     * Control: eqn(items) should find a row with SQL NULL.
     */
    public function testEqnMatchesSqlNull(): void
    {
        $rows = $this->materialize($this->dataStore->query(new RqlQuery('eqn(items)')));
        $this->assertCount(1, $rows, 'eqn(items) should return 1 row with SQL NULL.');
        $this->assertSame('sql-null', $rows[0]['purchase_order_number']);
    }

    /**
     * Data provider for JSON bug reproduction tests
     */
    public function jsonBugDataProvider(): iterable
    {
        yield 'empty array bug' => [
            'query' => 'eq(items,string:%5B%5D)', // []
            'expectedCount' => 0,
            'expectedSqlPattern' => '`items`=\'[]\'',
            'description' => 'Empty JSON array should return 0 due to wrong string comparison'
        ];

        yield 'empty object bug' => [
            'query' => 'eq(items,string:%7B%7D)', // {}
            'expectedCount' => 0,
            'expectedSqlPattern' => '`items`=\'{}\'',
            'description' => 'Empty JSON object should return 0 due to wrong string comparison'
        ];

        yield 'json null bug' => [
            'query' => 'eq(items,string:null)',
            'expectedCount' => 0,
            'expectedSqlPattern' => '`items`=\'null\'',
            'description' => 'JSON null should return 0 due to wrong string comparison'
        ];
    }

    /**
     * Test with bug reproduction
     *
     * @dataProvider jsonBugDataProvider
     */
    public function testJsonBugReproduction(
        string $query,
        int $expectedCount,
        string $expectedSqlPattern,
        string $description
    ): void {
        $this->markTestSkipped('Remove this line to reproduce the bug');

        $rows = $this->executeQuery($query);

        $this->assertCount($expectedCount, $rows, $description);

        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);
        $this->assertStringContainsString($expectedSqlPattern, $sql, "Expected SQL pattern '{$expectedSqlPattern}' not found in: {$sql}");
    }

    /**
     * Data provider for contains tests
     */
    public function containsTestDataProvider(): iterable
    {
        yield 'nm in purchase_order_number' => [
            'field' => 'purchase_order_number',
            'value' => 'nm',
            'expectedCount' => 7,
            'description' => 'Should find 7 rows with "nm" in purchase_order_number'
        ];
    }

    /**
     * Test contains queries
     *
     * @dataProvider containsTestDataProvider
     */
    public function testContainsQueries(
        string $field,
        string $value,
        int $expectedCount,
        string $description
    ): void {
        $rows = $this->executeQuery("contains({$field},string:{$value})");

        $this->assertCount($expectedCount, $rows, $description);

        foreach ($rows as $row) {
            $this->assertStringContainsString($value, $row[$field]);
        }

        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);
        $this->assertTrue(str_contains($sql, 'LIKE'), "SQL should contain LIKE. Got: {$sql}");
    }


    /**
     * Execute query and return materialized results
     */
    private function executeQuery(string $query): array
    {
        return $this->materialize($this->dataStore->query(new RqlQuery($query)));
    }

    /**
     * Convert iterable to array
     */
    private function materialize(iterable $rows): array
    {
        return is_array($rows) ? $rows : iterator_to_array($rows);
    }

    /**
     * Get the last executed SQL query from profiler
     */
    private function lastSql(): string
    {
        $profiles = $this->profiler->getProfiles();
        $this->assertNotEmpty($profiles, 'Profiler MUST contain at least 1 SQL request.');
        $last = end($profiles);
        return (string) ($last['sql'] ?? '');
    }
}
