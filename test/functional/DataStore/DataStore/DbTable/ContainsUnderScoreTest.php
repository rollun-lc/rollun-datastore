<?php

namespace functional\DataStore\DataStore\DbTable;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\Rql\RqlQuery;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Adapter\Profiler\Profiler;
use Zend\Db\TableGateway\TableGateway;
use rollun\datastore\Rql\Node\ContainsNode;

/**
 * feat(11tW8Zly): RQL contains "_" wildcard bug fix.
 * Bug summary:
 * `contains(...)` was translated to SQL `LIKE '%value%'` without escaping SQL wildcards.
 * In `LIKE`, `_` matches any single character and `%` matches any sequence, so searching for `PU_DS_NV__`
 * also matched values like `PU_DS_NV_NY_TX_WI__...` (false positives).
 * Fix: when building the `LIKE` pattern, escape `\`, `%`, and `_` (e.g., `\%`, `\_`) or use a substring function.
 * This test asserts the generated pattern is `%PU\_DS\_NV\_\_%` instead of `%PU_DS_NV__%`.
 */
final class ContainsUnderScoreTest extends TestCase
{
    /** @var Adapter */
    private $adapter;
    /** @var DbTable */
    private $ds;
    /** @var Profiler */
    private $profiler;

    protected function setUp(): void
    {
        $this->profiler = new \Laminas\Db\Adapter\Profiler\Profiler();

        $this->adapter = new \Laminas\Db\Adapter\Adapter([
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
                \PDO::ATTR_EMULATE_PREPARES => false, // см. примечание ниже
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ],
        ]);

        // схема для MySQL
        $this->adapter->query(
            'CREATE TABLE IF NOT EXISTS amazon_shipping_templates (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            template_code    VARCHAR(191) NOT NULL,
            shipping_service VARCHAR(191) NOT NULL,
            mln              VARCHAR(191) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            $this->adapter::QUERY_MODE_EXECUTE
        );

        $gw = new \Laminas\Db\TableGateway\TableGateway('amazon_shipping_templates', $this->adapter);
        $this->ds = new \rollun\datastore\DataStore\DbTable($gw, 'id');

        // data to insert
        $rows = [
            ['template_code' => 'PU_DS_NV__2025-03-20',         'shipping_service' => 'Standard Shipping', 'mln' => 'test'],
            ['template_code' => 'PU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Standard Shipping', 'mln' => 'test'],
            ['template_code' => 'PU_DS_NV__2025-03-20',         'shipping_service' => 'Free Economy', 'mln' => 'test'],
            ['template_code' => 'PU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Free Economy', 'mln' => 'test'],
            ['template_code' => 'PU_DS_NV__2025-03-20',         'shipping_service' => 'Expedited Shipping', 'mln' => 'test'],
            ['template_code' => 'PU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Expedited Shipping', 'mln' => 'test'],
            ['template_code' => '__PU_DS_NV__NY_TX_WI__2025-03-24', 'shipping_service' => 'Expedited Shipping', 'mln' => 'test'],
            ['template_code' => 'XXPU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Expedited Shipping', 'mln' => 'test'],
        ];
        foreach ($rows as $row) {
            $this->ds->create($row);
        }
    }

    /**
     * Check that LIKE-pattern is ecranated: %PU\_DS\_NV\_\_%
     */
    public function testContainsBuildsWithEscapedUnderscore(): void
    {
        $this->markTestSkipped();
        $this->ds->query(new RqlQuery('contains(template_code,string:PU_DS_NV__)'));

        $profiles = $this->profiler->getProfiles();
        $this->assertNotEmpty($profiles, 'Profiler must contains at least 1 SQL-request.');

        $last = end($profiles);
        $sql = (string) ($last['sql'] ?? '');

        $this->assertNotEmpty($sql, 'Last SQL must not be empty.');
        $this->assertTrue(
            str_contains($sql, 'LIKE'),
            "Last SQL must contains LIKE. Received: {$sql}"
        );

        $this->assertTrue(str_contains($sql, "%PU\_DS\_NV\_\_%"));
    }

    public function testEqMatchReturnsRows(): void
    {
        $q = new RqlQuery('eq(template_code,string:PU_DS_NV__2025%2D03%2D20)');
        $rows = $this->materialize($this->ds->query($q));

        $this->assertCount(3, $rows);
        foreach ($rows as $row) {
            $this->assertSame('PU_DS_NV__2025-03-20', $row['template_code']);
        }
    }

    /**
     * Optional tests. To see bug before fix
     */
    public function testUnderscoreWasWildcard(): void
    {
        $this->markTestSkipped('Enable manually to reproduce pre-fix behavior.');
        $rows = $this->materialize($this->ds->query(
            new RqlQuery('contains(template_code,string:PU_DS_NV__)')
        ));
        $this->assertCount(8, $rows);
    }

    public function testUnderscoreWasWildcardBeforeFirstSymbol(): void
    {
        // delete skipping test to see the bug (before fix)
        $this->markTestSkipped();
        $rows = $this->materialize($this->ds->query(
            new RqlQuery('contains(template_code,string:__PU_DS_NV__)')
        ));
        $this->assertCount(2, $rows);
    }

    private function materialize($rows): array
    {
        return is_array($rows) ? $rows : iterator_to_array($rows);
    }

    /**
     * Bug: URL-encoded strings with % symbols incorrectly trigger validation error.
     *
     * Problem: The containsNodeSpecSymbolsEcranation method checks for % and _ symbols
     * in the string, but it doesn't distinguish between SQL wildcard % and URL-encoded %
     * (like %22, %5F, %23). URL-encoded strings like "#dont_sell#" (encoded as %22%23dont%5Fsell%23)
     * should not trigger the validation error because % here is not a SQL wildcard.
     *
     * This test reproduces the bug - it should fail with current implementation.
     */
    public function testStringWithBackslashAndUnderscoreThrowsException(): void
    {
        $this->markTestSkipped();
        // This reproduces the bug: string with backslash AND underscore triggers validation error
        // Even though backslash here is not intended as SQL escape character
        try {
            // Create a query that will trigger the bug - string with both \ and _
            $query = new RqlQuery();
            $query->setQuery(new ContainsNode('template_code', 'path\\to_file'));
            $this->ds->query($query);
            $this->fail('Expected exception was not thrown');
        } catch (\rollun\datastore\DataStore\DataStoreException $e) {
            // Check if it's the expected exception message
            $fullMessage = $e->getMessage();
            if ($e->getPrevious()) {
                $fullMessage .= ' Previous: ' . $e->getPrevious()->getMessage();
            }
            $this->assertStringContainsString('Rql cannot contains backspace AND % OR _ in one request', $fullMessage);
        }
    }

    /**
     * Test that verifies the fix works correctly.
     * After fix, URL-encoded strings should be handled properly.
     */
    public function testUrlEncodedStringAfterFix(): void
    {
        $this->markTestSkipped();
        // This should work after fix - URL-encoded % should not be treated as SQL wildcard
        $this->ds->query(new RqlQuery('contains(template_code,string:%22%23dont%5Fsell%23)'));

        $profiles = $this->profiler->getProfiles();
        $this->assertNotEmpty($profiles, 'Profiler must contains at least 1 SQL-request.');

        $last = end($profiles);
        $sql = (string) ($last['sql'] ?? '');

        $this->assertNotEmpty($sql, 'Last SQL must not be empty.');
        $this->assertTrue(
            str_contains($sql, 'LIKE'),
            "Last SQL must contains LIKE. Received: {$sql}",
        );

        // Should escape _ properly in the decoded string
        $this->assertTrue(str_contains($sql, '%"#dont\_sell#%'));
    }

    public function testPersonal()
    {
        $rqlString = 'contains(tags,string:%22%23dont%5Fsell%23)';
        $this->expectException(DataStoreException::class);
//        $this->expectExceptionMessage("Can't build sql from rql query");
        $query = new RqlQuery($rqlString);
        $this->expectException(InvalidQueryException::class);
        $this->ds->query($query);
    }
}
