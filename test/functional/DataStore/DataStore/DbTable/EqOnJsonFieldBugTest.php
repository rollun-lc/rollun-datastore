<?php

declare(strict_types=1);

namespace functional\DataStore\DataStore\DbTable;

use Laminas\Db\Adapter\Profiler\Profiler;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\Rql\RqlQuery;
use rollun\datastore\TableGateway\TableManagerMysql;

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
final class EqOnJsonFieldBugTest extends TestCase
{
    private TableManagerMysql $mysqlManager;
    private ContainerInterface $container;
    private TableGateway $tableGateway;
    private DbTable $dataStore;
    private Profiler $profiler;
    private string $tableName = 'orders_json_eq_test';

    protected function setUp(): void
    {
        /** @var ContainerInterface $container */
        $this->container = include './config/container.php';
        $adapter = $this->container->get('db');
        
        // Add profiler to track SQL queries
        $this->profiler = new Profiler();
        $adapter->setProfiler($this->profiler);
        
        $this->mysqlManager = new TableManagerMysql($adapter);

        if ($this->mysqlManager->hasTable($this->tableName)) {
            $this->mysqlManager->deleteTable($this->tableName);
        }

        // Create table manually with proper JSON column and AUTO_INCREMENT
        // mysqlManager->createTable does not support JSON fields in tableConfig
        $adapter->query(
            "CREATE TABLE {$this->tableName} (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                purchase_order_number VARCHAR(191) NOT NULL,
                items JSON
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            $adapter::QUERY_MODE_EXECUTE
        );
        
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
        $testData = [
            // Empty arrays test cases
            ['purchase_order_number' => 'bulk return', 'items' => '[]'],
            ['purchase_order_number' => '', 'items' => '[]'],

            // Complex JSON objects
            ['purchase_order_number' => 'Canceled', 'items' => '[{"csn":"873-0056","rid":"IB99N","unitPrice":64.2,"warehouse":"TX","qtyOrdered":1,"qtyShipped":1,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '637686nm', 'items' => '[{"csn":"03060005","rid":"72D6L","unitPrice":99.3,"warehouse":null,"qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '644261nm', 'items' => '[{"csn":"03060003","rid":"4WXCA","unitPrice":125.1,"warehouse":null,"qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '650229nm', 'items' => '[{"csn":"37040193","rid":"MYF9N","unitPrice":50.92,"warehouse":null,"qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '657824nm', 'items' => '[{"csn":"03410011","rid":"39LDA","unitPrice":19.32,"warehouse":"NC","qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '635653nm', 'items' => '[{"csn":"234130","rid":"76ZRC","unitPrice":8.9,"warehouse":"2","qtyOrdered":3,"qtyShipped":3,"trackNumbers":["9400136208090275678269"],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '635747nm', 'items' => '[{"csn":"987170","rid":"1I3RU","unitPrice":8.04,"warehouse":"2","qtyOrdered":1,"qtyShipped":1,"trackNumbers":["390292096488"],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '635994nm', 'items' => '[{"csn":"163274","rid":"A5VRM","unitPrice":25.75,"warehouse":"3","qtyOrdered":1,"qtyShipped":1,"trackNumbers":["390323657983"],"qtyBackOrdered":0}]'],

            // Additional test cases for bugs
            ['purchase_order_number' => 'json-null', 'items' => 'null'], // JSON literal null
            ['purchase_order_number' => 'sql-null', 'items' => null],   // SQL NULL
        ];

        foreach ($testData as $row) {
            $this->dataStore->create($row);
        }
    }

    /**
     * Repro 1: eq(items,'[]') генерирует "`items`='[]'" и не находит пустые массивы.
     */
    public function testEqOnJsonFieldFailsToMatchEmptyArray(): void
    {
        $this->markTestSkipped('Remove this line to reproduce the bug');

        $q = new RqlQuery('eq(items,string:%5B%5D)'); // []
        $rows = $this->materialize($this->dataStore->query($q));

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

        $q = new RqlQuery('eq(items,string:%7B%7D)'); // {}
        $this->dataStore->query($q);

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

        $q = new RqlQuery('eq(items,string:null)');
        $rows = $this->materialize($this->dataStore->query($q));

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

    public function testContainsOnPurchaseOrderNumberFindsNm(): void
    {
        $q = new RqlQuery('contains(purchase_order_number,string:nm)');
        $rows = $this->materialize($this->dataStore->query($q));

        $this->assertCount(7, $rows, 'Ожидали 7 строк с "nm" в purchase_order_number');

        foreach ($rows as $row) {
            $this->assertStringContainsString('nm', $row['purchase_order_number']);
        }

        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);
        $this->assertTrue(str_contains($sql, 'LIKE'), "SQL должен содержать LIKE. Получено: {$sql}");
    }

    public function testEqEmptyArrayUsesJsonFuncsAndReturnsTwo(): void
    {
        $rows = $this->materialize($this->dataStore->query(new RqlQuery('eq(items,string:%5B%5D)')));

        // После фикса должны найтись 2 строки с пустым массивом
        $this->assertCount(2, $rows, 'Ожидали 2 строки с пустым JSON-массивом');

        // SQL должен использовать JSON_TYPE/JSON_LENGTH (а не `items`='[]')
        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);

        // JSON_TYPE(...)= 'ARRAY'
        $this->assertMatchesRegularExpression(
            '/JSON_TYPE\(\s*(?:`?\w+`?\.)?`?items`?\s*\)\s*=\s*\'ARRAY\'/i',
            $sql,
            "Ожидаем JSON_TYPE(items)='ARRAY'. SQL: {$sql}"
        );
        // JSON_LENGTH(...)= 0
        $this->assertMatchesRegularExpression(
            '/JSON_LENGTH\(\s*(?:`?\w+`?\.)?`?items`?\s*\)\s*=\s*0/i',
            $sql,
            "Ожидаем JSON_LENGTH(items)=0. SQL: {$sql}"
        );

        // И точно не строковое сравнение
        $this->assertStringNotContainsString("`items`='[]'", $sql, "Не должно быть `items`='[]'. SQL: {$sql}");
    }

    public function testEqJsonNullUsesCastAndReturnsOne(): void
    {
        $rows = $this->materialize($this->dataStore->query(new RqlQuery('eq(items,string:null)')));

        // Должна найтись 1 строка с JSON literal null (а не SQL NULL)
        $this->assertCount(1, $rows, 'Ожидали 1 строку с JSON null');
        $this->assertSame('json-null', $rows[0]['purchase_order_number']);

        // SQL: поле = CAST('null' AS JSON), не строковое сравнение
        $sql = $this->lastSql();
        $this->assertNotEmpty($sql);

        $this->assertMatchesRegularExpression(
            '~(?:`?\w+`?\.)?`?items`?\s*=\s*CAST\(\s*\'null\'\s*AS\s+JSON\)~i',
            $sql,
            "Ожидаем items = CAST('null' AS JSON). SQL: {$sql}"
        );

        $this->assertStringNotContainsString("`items`='null'", $sql, "Не должно быть `items`='null'. SQL: {$sql}");
    }


    private function materialize(iterable $rows): array
    {
        return is_array($rows) ? $rows : iterator_to_array($rows);
    }

    private function lastSql(): string
    {
        $profiles = $this->profiler->getProfiles();
        $this->assertNotEmpty($profiles, 'Profiler должен содержать хотя бы 1 SQL-запрос.');
        $last = end($profiles);
        return (string)($last['sql'] ?? '');
    }
}
