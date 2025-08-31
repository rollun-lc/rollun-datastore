<?php

namespace functional\DataStore\DataStore\DbTable;

use PHPUnit\Framework\TestCase;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\Rql\RqlQuery;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Adapter\Profiler\Profiler;
use Zend\Db\TableGateway\TableGateway;

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

        $rows = [
            ['template_code' => 'PU_DS_NV__2025-03-20',         'shipping_service' => 'Standard Shipping'],
            ['template_code' => 'PU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Standard Shipping'],
            ['template_code' => 'PU_DS_NV__2025-03-20',         'shipping_service' => 'Free Economy'],
            ['template_code' => 'PU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Free Economy'],
            ['template_code' => 'PU_DS_NV__2025-03-20',         'shipping_service' => 'Expedited Shipping'],
            ['template_code' => 'PU_DS_NV_NY_TX_WI__2025-03-24', 'shipping_service' => 'Expedited Shipping'],
        ];
        foreach ($rows as $row) {
            $this->ds->create($row);
        }
    }

    /**
     * Check that LIKE-pattern is ecranated: %PU\_DS\_NV\_\_%
     */
    public function testContainsBuildsEscapedLikePattern(): void
    {
        $it = $this->ds->query(new RqlQuery('contains(template_code,string:PU_DS_NV__)'));
        is_array($it) ? $it : iterator_to_array($it);

        $profiles = $this->profiler->getProfiles();
        $this->assertNotEmpty($profiles, 'Profiler must contain at least one SQL statement.');
        $last = end($profiles);

        $sqlRaw  = is_array($last) ? (isset($last['sql']) ? $last['sql'] : '') : (method_exists($last, 'getSql') ? $last->getSql() : '');
        $parsRaw = is_array($last) ? (isset($last['parameters']) ? $last['parameters'] : []) : (method_exists($last, 'getParameters') ? $last->getParameters() : []);

        $sql = (string)$sqlRaw;

        $params = [];
        if ($parsRaw instanceof ParameterContainer) {
            foreach ($parsRaw as $name => $meta) {
                $params[] = (is_array($meta) && array_key_exists('value', $meta)) ? $meta['value'] : $meta;
            }
        } elseif (is_array($parsRaw)) {
            $params = array_values($parsRaw);
        }

        $expected = "%PU\\_DS\\_NV\\_\\_%";

        $foundInParams = in_array($expected, $params, true);
        $foundInSql    = (false !== strpos($sql, "LIKE '$expected'"))
            || (false !== strpos($sql, 'LIKE "' . $expected . '"'));

        $this->assertTrue(
            $foundInParams || $foundInSql,
            "Expected escaped pattern {$expected}. SQL: {$sql}; params: " . json_encode($params)
        );
    }

    public function testEqExactMatchReturnsThreeRows(): void
    {
        $q = new RqlQuery('eq(template_code,string:PU_DS_NV__2025%2D03%2D20)');
        $rows = $this->materialize($this->ds->query($q));

        $this->assertCount(3, $rows);
        foreach ($rows as $r) {
            $this->assertSame('PU_DS_NV__2025-03-20', $r['template_code']);
        }
    }

    /**
     * Optional test. To see bug before fix
     */
    public function testRepro_UnderscoreWasWildcard(): void
    {
        $this->markTestSkipped('Enable manually to reproduce pre-fix behavior.');
        $rows = $this->materialize($this->ds->query(
            new RqlQuery('contains(template_code,string:PU_DS_NV__)')
        ));
        $this->assertCount(6, $rows);
    }

    private function materialize($rows): array
    {
        return is_array($rows) ? $rows : iterator_to_array($rows);
    }
}
