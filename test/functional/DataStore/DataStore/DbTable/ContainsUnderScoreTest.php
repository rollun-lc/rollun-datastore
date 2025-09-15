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
        $this->profiler = new Profiler();

        $this->adapter = new Adapter([
            'driver'   => 'Pdo_Mysql',
            'dsn'      => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'mysql',
                getenv('DB_PORT') ?: '3306',
                getenv('DB_NAME') ?: 'app_test'
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

        $this->adapter->query(
            'CREATE TABLE IF NOT EXISTS amazon_shipping_templates_underscore_test (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            template_code    VARCHAR(191) NOT NULL,
            shipping_service VARCHAR(191) NOT NULL,
            mln              VARCHAR(191) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            $this->adapter::QUERY_MODE_EXECUTE
        );

        $gw = new TableGateway('amazon_shipping_templates_underscore_test', $this->adapter);
        $this->ds = new DbTable($gw, 'id');

        $this->adapter->query('TRUNCATE TABLE amazon_shipping_templates_underscore_test', $this->adapter::QUERY_MODE_EXECUTE);

        // data to insert - covering various edge cases with % and _ symbols
        $rows = [
            // Original underscore test data
            ['template_code' => 'PU_DS_NV__2025-03-20',         'shipping_service' => 'Standard Shipping', 'mln' => 'test'],
            ['template_code' => 'PU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Standard Shipping', 'mln' => 'test'],
            ['template_code' => 'PU_DS_NV__2025-03-20',         'shipping_service' => 'Free Economy', 'mln' => 'test'],
            ['template_code' => 'PU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Free Economy', 'mln' => 'test'],
            ['template_code' => 'PU_DS_NV__2025-03-20',         'shipping_service' => 'Expedited Shipping', 'mln' => 'test'],
            ['template_code' => 'PU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Expedited Shipping', 'mln' => 'test'],
            ['template_code' => '__PU_DS_NV__NY_TX_WI__2025-03-24', 'shipping_service' => 'Expedited Shipping', 'mln' => 'test'],
            ['template_code' => 'XXPU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Expedited Shipping', 'mln' => 'test'],

            // Test data for percent symbol scenarios
            ['template_code' => 'test%value_2025',              'shipping_service' => 'Express', 'mln' => 'percent_test'],
            ['template_code' => 'test_percent_value_2025',      'shipping_service' => 'Express', 'mln' => 'percent_test'],
            ['template_code' => 'testXvalue_2025',              'shipping_service' => 'Express', 'mln' => 'percent_test'],

            // Test data for combined % and _ scenarios
            ['template_code' => 'order_%_status_active',        'shipping_service' => 'Premium', 'mln' => 'combined_test'],
            ['template_code' => 'order_XX_status_active',       'shipping_service' => 'Premium', 'mln' => 'combined_test'],
            ['template_code' => 'order_pending_status_active',  'shipping_service' => 'Premium', 'mln' => 'combined_test'],

            // URL-encoded scenario test data
            ['template_code' => '"#dont_sell#"',                'shipping_service' => 'Special', 'mln' => 'url_test'],
            ['template_code' => '"#dont_sell_more#"',           'shipping_service' => 'Special', 'mln' => 'url_test'],
            ['template_code' => '"#dontXsell#"',                'shipping_service' => 'Special', 'mln' => 'url_test'],
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
     * Test that percent symbol is properly escaped in contains queries.
     * Should escape % as \% to prevent SQL wildcard matching.
     */
    public function testContainsEscapesPercentSymbol(): void
    {
        // Create query programmatically to avoid RQL parser issues with %
        $query = new RqlQuery();
        $query->setQuery(new ContainsNode('template_code', 'test%value'));
        $this->ds->query($query);

        $profiles = $this->profiler->getProfiles();
        $this->assertNotEmpty($profiles, 'Profiler must contains at least 1 SQL-request.');

        $last = end($profiles);
        $sql = (string) ($last['sql'] ?? '');

        $this->assertNotEmpty($sql, 'Last SQL must not be empty.');
        $this->assertTrue(str_contains($sql, 'LIKE'), "SQL must contain LIKE. Received: {$sql}");

        // Check that % is escaped as \%
        $this->assertTrue(str_contains($sql, '%test\%value%'), "SQL should contain escaped percent: {$sql}");

        // Check that only exact matches are found (not wildcard matches)
        $query2 = new RqlQuery();
        $query2->setQuery(new ContainsNode('template_code', 'test%value'));
        $rows = $this->materialize($this->ds->query($query2));
        $this->assertCount(1, $rows, 'Should find exactly 1 row with test%value');
        $this->assertSame('test%value_2025', $rows[0]['template_code']);
    }

    /**
     * Test that both percent and underscore symbols are properly escaped together.
     * Should escape both % as \% and _ as \_ to prevent SQL wildcard matching.
     */
    public function testContainsEscapesBothPercentAndUnderscore(): void
    {
        // Create query programmatically to avoid RQL parser issues with %
        $query = new RqlQuery();
        $query->setQuery(new ContainsNode('template_code', 'order_%_status'));
        $this->ds->query($query);

        $profiles = $this->profiler->getProfiles();
        $this->assertNotEmpty($profiles, 'Profiler must contains at least 1 SQL-request.');

        $last = end($profiles);
        $sql = (string) ($last['sql'] ?? '');

        $this->assertNotEmpty($sql, 'Last SQL must not be empty.');
        $this->assertTrue(str_contains($sql, 'LIKE'), "SQL must contain LIKE. Received: {$sql}");

        // Check that both % and _ are escaped
        $this->assertTrue(str_contains($sql, '%order\_\%\_status%'), "SQL should contain both escaped symbols: {$sql}");

        // Check that only exact matches are found (not wildcard matches)
        $query2 = new RqlQuery();
        $query2->setQuery(new ContainsNode('template_code', 'order_%_status'));
        $rows = $this->materialize($this->ds->query($query2));
        $this->assertCount(1, $rows, 'Should find exactly 1 row with order_%_status');
        $this->assertSame('order_%_status_active', $rows[0]['template_code']);
    }

    /**
     * Test edge case with only special characters.
     * Should properly escape and find exact matches.
     */
    public function testContainsWithOnlySpecialCharacters(): void
    {
        // Add test data with only special chars
        $this->ds->create(['template_code' => '_%', 'shipping_service' => 'Special', 'mln' => 'edge_case']);
        $this->ds->create(['template_code' => '_X', 'shipping_service' => 'Special', 'mln' => 'edge_case']);

        // Create query programmatically to avoid RQL parser issues with %
        $query = new RqlQuery();
        $query->setQuery(new ContainsNode('template_code', '_%'));
        $this->ds->query($query);

        $profiles = $this->profiler->getProfiles();
        $last = end($profiles);
        $sql = (string) ($last['sql'] ?? '');

        // Check proper escaping
        $this->assertTrue(str_contains($sql, '%\_\%%'), "SQL should escape both characters: {$sql}");

        // Check exact match count - only '_%' should be found
        $query2 = new RqlQuery();
        $query2->setQuery(new ContainsNode('template_code', '_%'));
        $rows = $this->materialize($this->ds->query($query2));

        // Both rows should be found: '_%' and 'order_%_status_active' (contains '_%')
        $foundCodes = array_column($rows, 'template_code');
        $this->assertCount(2, $rows, 'Should find 2 rows containing _%' . '. Found: ' . implode(', ', $foundCodes));
        $this->assertContains('_%', $foundCodes);
        $this->assertContains('order_%_status_active', $foundCodes);
    }

    /**
     * Test that verifies the fix works correctly.
     * After fix, URL-encoded strings should be handled properly.
     */
    public function testContainsPrepareValuesCorrectWithSpecSymbolsAndMysqlQuote(): void
    {
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
        $this->assertTrue(str_contains($sql, '%\"#dont\_sell#%'));

        // Check that only exact matches are found
        $rows = $this->materialize($this->ds->query(new RqlQuery('contains(template_code,string:%22%23dont%5Fsell%23)')));
        $this->assertCount(1, $rows, 'Should find exactly 1 row with "#dont_sell#"');
        $this->assertSame('"#dont_sell#"', $rows[0]['template_code']);
    }
}
