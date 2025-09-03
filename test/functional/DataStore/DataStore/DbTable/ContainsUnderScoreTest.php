<?php

namespace functional\DataStore\DataStore\DbTable;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Profiler\Profiler;
use Laminas\Db\TableGateway\TableGateway;
use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\Rql\RqlQuery;

/**
 * feat(11tW8Zly): rql contains _ wildcard symbol bug.
 * Bug summary:
 * `contains(...)` was translated to SQL `LIKE '%value%'` without escaping SQL wildcards.
 * In `LIKE`, `_` matches any single character and `%` matches any sequence, so searching for `PU_DS_NV__` also matched
 * values like `PU_DS_NV_NY_TX_WI__...` (false positives).
 * Fix: when building the `LIKE` pattern, escape `\`, `%`, and `_` (e.g., `\%`, `\_`) or use a substring function.
 * This test asserts the generated pattern is `%PU\_DS\_NV\_\_%` instead of `%PU_DS_NV__%`.
 */
final class ContainsUnderScoreTest extends TestCase
{
    private Adapter $adapter;
    private DbTable $ds;
    private Profiler $profiler;

    protected function setUp(): void
    {
        $this->profiler = new Profiler();
        $this->adapter = new Adapter([
            'driver'   => 'Pdo_Sqlite',
            'database' => ':memory:',
            'profiler' => $this->profiler,
            'options'  => [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
        ]);

        $this->adapter->query(
            'CREATE TABLE amazon_shipping_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                template_code TEXT NOT NULL,
                shipping_service TEXT NOT NULL
            )',
            $this->adapter::QUERY_MODE_EXECUTE
        );

        $gw = new TableGateway('amazon_shipping_templates', $this->adapter);
        $this->ds = new DbTable($gw, 'id');

        // data to insert
        $rows = [
            ['template_code' => 'PU_DS_NV__2025-03-20',         'shipping_service' => 'Standard Shipping'],
            ['template_code' => 'PU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Standard Shipping'],
            ['template_code' => 'PU_DS_NV__2025-03-20',         'shipping_service' => 'Free Economy'],
            ['template_code' => 'PU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Free Economy'],
            ['template_code' => 'PU_DS_NV__2025-03-20',         'shipping_service' => 'Expedited Shipping'],
            ['template_code' => 'PU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Expedited Shipping'],
            ['template_code' => '__PU_DS_NV__NY_TX_WI__2025-03-24', 'shipping_service' => 'Expedited Shipping'],
            ['template_code' => 'XXPU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Expedited Shipping'],
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
        // delete skipping test to see the bug (before fix)
        $this->markTestSkipped();
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
}
