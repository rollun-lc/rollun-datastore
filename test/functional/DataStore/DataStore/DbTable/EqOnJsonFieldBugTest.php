<?php

declare(strict_types=1);

namespace functional\DataStore\DataStore\DbTable;

use Laminas\Db\Adapter\Profiler\Profiler;
use Laminas\Db\TableGateway\TableGateway;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\Rql\RqlQuery;
use rollun\datastore\TableGateway\TableManagerMysql;
use rollun\test\functional\FunctionalTestCase;

/**
 * Bug summary (eq-on-JSON):
 * Для запроса RQL eq(items,string:%5B%5D) билдер генерирует SQL вида:
 *   WHERE items = '[]'
 * где items — колонка типа JSON. Такое "бинарное" сравнение JSON с текстом '[]'
 * в MySQL возвращает FALSE, поэтому пустые массивы не находятся.
 *
 * Правильные варианты сопоставления:
 *   - WHERE JSON_TYPE(items) = 'ARRAY' AND JSON_LENGTH(items) = 0
 *   - WHERE items = CAST('[]' AS JSON)
 *
 * Тест ниже воспроизводит баг (eq по пустому JSON-массиву возвращает 0 строк),
 * а также содержит кейс, подтверждающий, что contains по строковому полю работает корректно.
 */
final class EqOnJsonFieldBugTest extends FunctionalTestCase
{
    private TableManagerMysql $mysqlManager;
    private TableGateway $tableGateway;
    private DbTable $dataStore;
    private Profiler $profiler;
    private string $tableName = 'orders_json_eq_test';

    /**
     * Table configuration with JSON support
     */
    private function getTableConfig(): array
    {
        return [
            'id' => [
                'field_type' => 'Integer',
                'field_primary_key' => true,
            ],
            'purchase_order_number' => [
                'field_type' => 'Varchar',
                'field_params' => [
                    'length' => 255,
                ],
            ],
            'items' => [
                'field_type' => 'Json',
                'field_params' => [
                    'nullable' => true,
                ],
            ],
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
    public function getTestData(): array
    {
        return [
            // Empty arrays test cases
            ['id' => 1, 'purchase_order_number' => 'bulk return', 'items' => '[]'],
            ['id' => 2, 'purchase_order_number' => '', 'items' => '[]'],

            // Complex JSON objects
            ['id' => 3, 'purchase_order_number' => 'Canceled', 'items' => '[{"csn":"873-0056","rid":"IB99N","unitPrice":64.2,"warehouse":"TX","qtyOrdered":1,"qtyShipped":1,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['id' => 4, 'purchase_order_number' => '637686nm', 'items' => '[{"csn":"03060005","rid":"72D6L","unitPrice":99.3,"warehouse":null,"qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['id' => 5, 'purchase_order_number' => '644261nm', 'items' => '[{"csn":"03060003","rid":"4WXCA","unitPrice":125.1,"warehouse":null,"qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['id' => 6, 'purchase_order_number' => '650229nm', 'items' => '[{"csn":"37040193","rid":"MYF9N","unitPrice":50.92,"warehouse":null,"qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['id' => 7, 'purchase_order_number' => '657824nm', 'items' => '[{"csn":"03410011","rid":"39LDA","unitPrice":19.32,"warehouse":"NC","qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['id' => 8, 'purchase_order_number' => '635653nm', 'items' => '[{"csn":"234130","rid":"76ZRC","unitPrice":8.9,"warehouse":"2","qtyOrdered":3,"qtyShipped":3,"trackNumbers":["9400136208090275678269"],"qtyBackOrdered":0}]'],
            ['id' => 9, 'purchase_order_number' => '635747nm', 'items' => '[{"csn":"987170","rid":"1I3RU","unitPrice":8.04,"warehouse":"2","qtyOrdered":1,"qtyShipped":1,"trackNumbers":["390292096488"],"qtyBackOrdered":0}]'],
            ['id' => 10, 'purchase_order_number' => '635994nm', 'items' => '[{"csn":"163274","rid":"A5VRM","unitPrice":25.75,"warehouse":"3","qtyOrdered":1,"qtyShipped":1,"trackNumbers":["390323657983"],"qtyBackOrdered":0}]'],

            // Additional test cases for bugs
            ['id' => 11, 'purchase_order_number' => 'json-null', 'items' => 'null'], // JSON literal null
            ['id' => 12, 'purchase_order_number' => 'sql-null', 'items' => null],   // SQL NULL
        ];
    }

    /**
     * Data provider for JSON query tests
     */
    public function jsonQueryDataProvider(): array
    {
        return [
            'empty array' => [
                'query' => 'eq(items,string:%5B%5D)',
                'expectedCount' => 2,
                'expectedSqlPattern' => '/JSON_TYPE\(\s*(?:`?\w+`?\.)?`?items`?\s*\)\s*=\s*\'ARRAY\'/i',
                'description' => 'Empty JSON array should use JSON_TYPE and JSON_LENGTH functions'
            ],
            'json null' => [
                'query' => 'eq(items,string:null)',
                'expectedCount' => 1,
                'expectedSqlPattern' => '~(?:`?\w+`?\.)?`?items`?\s*=\s*CAST\(\s*\'null\'\s*AS\s+JSON\)~i',
                'description' => 'JSON null should use CAST function'
            ],
            'empty object' => [
                'query' => 'eq(items,string:%7B%7D)',
                'expectedCount' => 0,
                'expectedSqlPattern' => '/JSON_TYPE\(\s*(?:`?\w+`?\.)?`?items`?\s*\)\s*=\s*\'OBJECT\'/i',
                'description' => 'Empty object should use JSON_TYPE and JSON_LENGTH functions'
            ],
        ];
    }

    /**
     * Test JSON field equality queries with data provider
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
     * Repro 1: eq(items,'[]') генерирует "`items`='[]'" и не находит пустые массивы.
     */
    public function testEqOnJsonFieldFailsToMatchEmptyArray(): void
    {
        $this->markTestSkipped('Remove this line to reproduce the bug');

        $rows = $this->executeQuery('eq(items,string:%5B%5D)'); // []

        $this->assertCount(0, $rows, 'Ожидали 0 из-за неверного сравнения JSON = \'[]\'.');

        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);
        $this->assertStringContainsString("`items`='[]'", $sql, "Должно быть бинарное сравнение с '[]'. SQL: {$sql}");
    }

    /**
     * Repro 2: eq(items,'{}') тоже строится как текстовое равенство "`items`='{}'".
     */
    public function testEqOnJsonFieldBuildsWrongEqualityForEmptyObject(): void
    {
        $this->markTestSkipped('Remove this line to reproduce the bug');

        $rows = $this->executeQuery('eq(items,string:%7B%7D)'); // {}

        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);
        $this->assertStringContainsString("`items`='{}'", $sql, "Ожидали ошибочное равенство `items`='{}'. SQL: {$sql}");
    }

    /**
     * Repro 3: eq(items,'null') — JSON null не находится, а SQL сравнивает с текстом 'null'.
     * (Отдельно от SQL NULL, который проверяется eqn(items))
     */
    public function testEqOnJsonFieldFailsForJsonNullAndBuildsWrongSql(): void
    {
        $this->markTestSkipped('Remove this line to reproduce the bug');

        $rows = $this->executeQuery('eq(items,string:null)');

        // В таблице есть строка с JSON null, но сравнение как с текстом 'null' вернёт 0
        $this->assertCount(0, $rows, 'Ожидали 0: JSON null не найден из-за строкового сравнения.');

        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);
        $this->assertStringContainsString("`items`='null'", $sql, "Ожидали `items`='null' в SQL (неправильно). SQL: {$sql}");
    }

    /**
     * Контроль: eqn(items) должен находить строку с SQL NULL.
     */
    public function testEqnMatchesSqlNull(): void
    {
        $rows = $this->materialize($this->dataStore->query(new RqlQuery('eqn(items)')));
        $this->assertCount(1, $rows, 'eqn(items) должен вернуть 1 строку с SQL NULL.');
        $this->assertSame('sql-null', $rows[0]['purchase_order_number']);
    }

    /**
     * Data provider for contains tests
     */
    public function containsTestDataProvider(): array
    {
        return [
            'nm in purchase_order_number' => [
                'field' => 'purchase_order_number',
                'value' => 'nm',
                'expectedCount' => 7,
                'description' => 'Should find 7 rows with "nm" in purchase_order_number'
            ],
        ];
    }

    /**
     * Test contains queries with data provider
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
        $this->assertTrue(str_contains($sql, 'LIKE'), "SQL должен содержать LIKE. Получено: {$sql}");
    }

    public function testContainsOnPurchaseOrderNumberFindsNm(): void
    {
        $rows = $this->executeQuery('contains(purchase_order_number,string:nm)');

        $this->assertCount(7, $rows, 'Ожидали 7 строк с "nm" в purchase_order_number');

        foreach ($rows as $row) {
            $this->assertStringContainsString('nm', $row['purchase_order_number']);
        }

        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);
        $this->assertTrue(str_contains($sql, 'LIKE'), "SQL должен содержать LIKE. Получено: {$sql}");
    }

    /**
     * Test that empty JSON array query uses proper JSON functions after fix
     */
    public function testEqEmptyArrayUsesJsonFuncsAndReturnsTwo(): void
    {
        $rows = $this->executeQuery('eq(items,string:%5B%5D)');

        $this->assertCount(2, $rows, 'Ожидали 2 строки с пустым JSON-массивом');

        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);

        // Should use JSON_TYPE and JSON_LENGTH functions
        $this->assertMatchesRegularExpression(
            '/JSON_TYPE\(\s*(?:`?\w+`?\.)?`?items`?\s*\)\s*=\s*\'ARRAY\'/i',
            $sql,
            "Ожидаем JSON_TYPE(items)='ARRAY'. SQL: {$sql}"
        );
        
        $this->assertMatchesRegularExpression(
            '/JSON_LENGTH\(\s*(?:`?\w+`?\.)?`?items`?\s*\)\s*=\s*0/i',
            $sql,
            "Ожидаем JSON_LENGTH(items)=0. SQL: {$sql}"
        );

        // Should NOT use string comparison
        $this->assertStringNotContainsString("`items`='[]'", $sql, "Не должно быть `items`='[]'. SQL: {$sql}");
    }

    /**
     * Test that JSON null query uses CAST function after fix
     */
    public function testEqJsonNullUsesCastAndReturnsOne(): void
    {
        $rows = $this->executeQuery('eq(items,string:null)');

        $this->assertCount(1, $rows, 'Ожидали 1 строку с JSON null');
        $this->assertSame('json-null', $rows[0]['purchase_order_number']);

        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);

        // Should use CAST function
        $this->assertMatchesRegularExpression(
            '~(?:`?\w+`?\.)?`?items`?\s*=\s*CAST\(\s*\'null\'\s*AS\s+JSON\)~i',
            $sql,
            "Ожидаем items = CAST('null' AS JSON). SQL: {$sql}"
        );

        // Should NOT use string comparison
        $this->assertStringNotContainsString("`items`='null'", $sql, "Не должно быть `items`='null'. SQL: {$sql}");
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
        $this->assertNotEmpty($profiles, 'Profiler MUST contains at least 1 SQL-request.');
        $last = end($profiles);
        return (string)($last['sql'] ?? '');
    }

    /**
     * Assert SQL contains specific pattern
     */
    private function assertSqlContains(string $pattern, string $message = ''): void
    {
        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);
        $this->assertMatchesRegularExpression($pattern, $sql, $message ?: "SQL should match pattern: {$sql}");
    }

    /**
     * Assert SQL does not contain specific string
     */
    private function assertSqlNotContains(string $string, string $message = ''): void
    {
        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);
        $this->assertStringNotContainsString($string, $sql, $message ?: "SQL should not contain: {$string}. SQL: {$sql}");
    }
}
