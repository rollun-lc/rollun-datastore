<?php

namespace functional\DataStore\DataStore\DbTable;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Profiler\Profiler;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\Rql\RqlQuery;

final class EqOnJsonFieldBugTest extends TestCase
{
    private Adapter $adapter;
    private DbTable $ds;
    private Profiler $profiler;

    protected function setUp(): void
    {
        $this->profiler = new Profiler();

        $this->adapter = new Adapter([
            'driver'   => 'Pdo_Mysql',
            'dsn'      => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'mysql',
                getenv('DB_PORT') ?: '3306',
                getenv('DB_NAME') ?: 'app_test',
            ),
            'username' => getenv('DB_USER') ?: 'app',
            'password' => getenv('DB_PASS') ?: 'secret',
            'profiler' => $this->profiler,
            'options'  => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ],
        ]);

        // JSON-таблица (items допускает NULL)
        $this->adapter->query(
            'CREATE TABLE IF NOT EXISTS orders_json_eq_test (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                purchase_order_number VARCHAR(191) NOT NULL,
                items JSON
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            $this->adapter::QUERY_MODE_EXECUTE
        );
        $this->adapter->query('TRUNCATE TABLE orders_json_eq_test', $this->adapter::QUERY_MODE_EXECUTE);

        $gw = new TableGateway('orders_json_eq_test', $this->adapter);
        $this->ds = new DbTable($gw, 'id');

        // Базовые данные
        $rows = [
            ['purchase_order_number' => 'bulk return', 'items' => '[]'],
            ['purchase_order_number' => '',            'items' => '[]'],

            ['purchase_order_number' => 'Canceled',  'items' => '[{"csn":"873-0056","rid":"IB99N","unitPrice":64.2,"warehouse":"TX","qtyOrdered":1,"qtyShipped":1,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '637686nm',  'items' => '[{"csn":"03060005","rid":"72D6L","unitPrice":99.3,"warehouse":null,"qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '644261nm',  'items' => '[{"csn":"03060003","rid":"4WXCA","unitPrice":125.1,"warehouse":null,"qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '650229nm',  'items' => '[{"csn":"37040193","rid":"MYF9N","unitPrice":50.92,"warehouse":null,"qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '657824nm',  'items' => '[{"csn":"03410011","rid":"39LDA","unitPrice":19.32,"warehouse":"NC","qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '635653nm',  'items' => '[{"csn":"234130","rid":"76ZRC","unitPrice":8.9,"warehouse":"2","qtyOrdered":3,"qtyShipped":3,"trackNumbers":["9400136208090275678269"],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '635747nm',  'items' => '[{"csn":"987170","rid":"1I3RU","unitPrice":8.04,"warehouse":"2","qtyOrdered":1,"qtyShipped":1,"trackNumbers":["390292096488"],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '635994nm',  'items' => '[{"csn":"163274","rid":"A5VRM","unitPrice":25.75,"warehouse":"3","qtyOrdered":1,"qtyShipped":1,"trackNumbers":["390323657983"],"qtyBackOrdered":0}]'],

            // ДОП для багов:
            ['purchase_order_number' => 'json-null',  'items' => 'null'], // JSON literal null
            ['purchase_order_number' => 'sql-null',   'items' => null],   // SQL NULL
        ];

        foreach ($rows as $r) {
            $this->ds->create($r);
        }
    }

    /**
     * Repro 1: eq(items,'[]') генерирует "`items`='[]'" и не находит пустые массивы.
     */
    public function testEqOnJsonFieldFailsToMatchEmptyArray(): void
    {
        $this->markTestSkipped('Remove this line to reproduce the bug');

        $q = new RqlQuery('eq(items,string:%5B%5D)'); // []
        $rows = $this->materialize($this->ds->query($q));

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
        $this->ds->query($q);

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
        $rows = $this->materialize($this->ds->query($q));

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
        $rows = $this->materialize($this->ds->query(new RqlQuery('eqn(items)')));
        $this->assertCount(1, $rows, 'eqn(items) должен вернуть 1 строку с SQL NULL.');
        $this->assertSame('sql-null', $rows[0]['purchase_order_number']);
    }

    public function testContainsOnPurchaseOrderNumberFindsNm(): void
    {
        $q = new RqlQuery('contains(purchase_order_number,string:nm)');
        $rows = $this->materialize($this->ds->query($q));

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
        $rows = $this->materialize($this->ds->query(new RqlQuery('eq(items,string:%5B%5D)')));

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
        $rows = $this->materialize($this->ds->query(new RqlQuery('eq(items,string:null)')));

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


    private function materialize($rows): array
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

    protected function tearDown(): void
    {
        try {
            if (isset($this->adapter)) {
                $this->adapter->query(
                    'DROP TABLE IF EXISTS orders_json_eq_test',
                    $this->adapter::QUERY_MODE_EXECUTE
                );
            }
        } catch (\Throwable $e) {
            // гасим, чтобы не маскировать исходное падение теста
        } finally {
            parent::tearDown();
        }
    }
}
