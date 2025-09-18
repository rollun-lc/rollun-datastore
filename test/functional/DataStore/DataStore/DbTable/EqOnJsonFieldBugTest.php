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
 * Этот тест подтверждает, что текущая реализация генерирует "= '[]'" и не находит записи,
 * несмотря на наличие строк с пустым массивом.
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
                items JSON NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            $this->adapter::QUERY_MODE_EXECUTE
        );

        $this->adapter->query('TRUNCATE TABLE orders_json_eq_test', $this->adapter::QUERY_MODE_EXECUTE);

        $gw = new TableGateway('orders_json_eq_test', $this->adapter);
        $this->ds = new DbTable($gw, 'id');

        // Данные (достаточно пары пустых массивов и пары непустых)
        $rows = [
            ['purchase_order_number' => 'bulk return', 'items' => '[]'],
            ['purchase_order_number' => '',            'items' => '[]'],
            ['purchase_order_number' => 'Canceled',    'items' => '[{"csn":"873-0056","rid":"IB99N","unitPrice":64.2,"warehouse":"TX","qtyOrdered":1,"qtyShipped":1,"trackNumbers":[],"qtyBackOrdered":0}]'],
            ['purchase_order_number' => '637686nm',    'items' => '[{"csn":"03060005","rid":"72D6L","unitPrice":99.3,"warehouse":null,"qtyOrdered":1,"qtyShipped":0,"trackNumbers":[],"qtyBackOrdered":0}]'],
        ];

        foreach ($rows as $r) {
            // Вставляем как строки — MySQL сам приведёт к JSON
            $this->ds->create($r);
        }
    }

    public function testEqOnJsonFieldFailsToMatchEmptyArray(): void
    {
        // when: RQL eq по пустому массиву
        $q = new RqlQuery('eq(items,string:%5B%5D)');
        $result = $this->ds->query($q);

        // then: материализуем и проверяем, что НИЧЕГО не найдено (текущее баговое поведение)
        $rows = is_array($result) ? $result : iterator_to_array($result);
        $this->assertCount(
            0,
            $rows,
            'Bug reproduction: ожидали 0 из-за неверного сравнения JSON = \'[]\'. На самом деле в таблице 2 строки с пустым массивом.'
        );

        // И дополнительно зафиксируем, что сгенерированный SQL действительно содержит "= \'[]\'"
        $profiles = $this->profiler->getProfiles();
        $this->assertNotEmpty($profiles, 'Profiler должен содержать хотя бы 1 SQL-запрос.');
        $last = end($profiles);
        $sql = (string)($last['sql'] ?? '');
        $this->assertNotEmpty($sql, 'Последний SQL не должен быть пустым.');
        $this->assertStringContainsString(
            "items = '[]'",
            $sql,
            "Ожидали, что билдер генерирует бинарное сравнение JSON с текстом: {$sql}"
        );

        // (необязательная подсказка для разработчика — комментарий)
        // Правильная проверка пустого массива для MySQL: JSON_LENGTH(items)=0.
        // Либо равенство с CAST('[]' AS JSON).
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
            // гасим ошибки, чтобы не маскировать исходное падение теста
        } finally {
            parent::tearDown();
        }
    }

}
