<?php

namespace functional\DataStore\DataStore\DbTable;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Profiler\Profiler;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\Rql\RqlQuery;

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

        // JSON-таблица для воспроизведения
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

        // Полный набор данных из сообщения пользователя
        $rows = [
            ['purchase_order_number' => 'bulk return', 'items' => '[]'],
            ['purchase_order_number' => '',            'items' => '[]'],

            ['purchase_order_number' => 'Canceled', 'items' => '[{"csn": "873-0056", "rid": "IB99N", "unitPrice": 64.2, "warehouse": "TX", "qtyOrdered": 1, "qtyShipped": 1, "trackNumbers": [], "qtyBackOrdered": 0}]'],
            ['purchase_order_number' => '637686nm', 'items' => '[{"csn": "03060005", "rid": "72D6L", "unitPrice": 99.3, "warehouse": null, "qtyOrdered": 1, "qtyShipped": 0, "trackNumbers": [], "qtyBackOrdered": 0}]'],
            ['purchase_order_number' => '644261nm', 'items' => '[{"csn": "03060003", "rid": "4WXCA", "unitPrice": 125.1, "warehouse": null, "qtyOrdered": 1, "qtyShipped": 0, "trackNumbers": [], "qtyBackOrdered": 0}]'],
            ['purchase_order_number' => '650229nm', 'items' => '[{"csn": "37040193", "rid": "MYF9N", "unitPrice": 50.92, "warehouse": null, "qtyOrdered": 1, "qtyShipped": 0, "trackNumbers": [], "qtyBackOrdered": 0}]'],
            ['purchase_order_number' => '657824nm', 'items' => '[{"csn": "03410011", "rid": "39LDA", "unitPrice": 19.32, "warehouse": "NC", "qtyOrdered": 1, "qtyShipped": 0, "trackNumbers": [], "qtyBackOrdered": 0}]'],
            ['purchase_order_number' => '635653nm', 'items' => '[{"csn": "234130", "rid": "76ZRC", "unitPrice": 8.9, "warehouse": "2", "qtyOrdered": 3, "qtyShipped": 3, "trackNumbers": ["9400136208090275678269"], "qtyBackOrdered": 0}]'],
            ['purchase_order_number' => '635747nm', 'items' => '[{"csn": "987170", "rid": "1I3RU", "unitPrice": 8.04, "warehouse": "2", "qtyOrdered": 1, "qtyShipped": 1, "trackNumbers": ["390292096488"], "qtyBackOrdered": 0}]'],
            ['purchase_order_number' => '635994nm', 'items' => '[{"csn": "163274", "rid": "A5VRM", "unitPrice": 25.75, "warehouse": "3", "qtyOrdered": 1, "qtyShipped": 1, "trackNumbers": ["390323657983"], "qtyBackOrdered": 0}]'],
        ];

        foreach ($rows as $r) {
            // Вставляем как строки — MySQL приведёт к JSON
            $this->ds->create($r);
        }
    }

    public function testEqOnJsonFieldFailsToMatchEmptyArray(): void
    {
        $this->markTestSkipped('Remove this line to reproduce the bug');
        // BUG: eq(items,string:%5B%5D) генерирует "items = '[]'" и не находит пустые массивы (ожидаем 0)
        $q = new RqlQuery('eq(items,string:%5B%5D)');
        $result = $this->ds->query($q);

        $rows = is_array($result) ? $result : iterator_to_array($result);
        $this->assertCount(
            0,
            $rows,
            'Bug reproduction: ожидали 0 из-за неверного сравнения JSON = \'[]\'. В таблице фактически есть 2 строки с пустым массивом.'
        );

        // Зафиксируем, что SQL действительно содержит "= \'[]\'"
        $profiles = $this->profiler->getProfiles();
        $this->assertNotEmpty($profiles, 'Profiler должен содержать хотя бы 1 SQL-запрос.');
        $last = end($profiles);
        $sql = (string)($last['sql'] ?? '');
        $this->assertNotEmpty($sql, 'Последний SQL не должен быть пустым.');
        $this->assertStringContainsString(
            "`items`='[]'",
            $sql,
            "Ожидали, что билдер генерирует бинарное сравнение JSON с текстом: {$sql}"
        );
    }

    public function testContainsOnPurchaseOrderNumberFindsNm(): void
    {
        // Проверяем, что contains корректно ищет подстроку по строковому полю
        $q = new RqlQuery('contains(purchase_order_number,string:nm)');
        $rows = $this->ds->query($q);
        $rows = is_array($rows) ? $rows : iterator_to_array($rows);

        // Ожидаем 7 записей с окончанием "nm"
        $this->assertCount(7, $rows, 'Ожидали 7 строк с подстрокой "nm" в purchase_order_number');

        // Быстрый sanity-check: все найденные действительно содержат "nm"
        foreach ($rows as $row) {
            $this->assertStringContainsString('nm', $row['purchase_order_number']);
        }

        // И заодно посмотрим, что SQL LIKE действительно присутствует
        $profiles = $this->profiler->getProfiles();
        $this->assertNotEmpty($profiles, 'Profiler должен содержать хотя бы 1 SQL-запрос.');
        $last = end($profiles);
        $sql = (string)($last['sql'] ?? '');
        $this->assertNotEmpty($sql, 'Последний SQL не должен быть пустым.');
        $this->assertTrue(str_contains($sql, 'LIKE'), "SQL должен содержать LIKE. Получено: {$sql}");
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
            // не мешаем исходному фейлу теста
        } finally {
            parent::tearDown();
        }
    }
}
