<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataSource;

use Xiag\Rql\Parser\Query;
use rollun\datastore\DataStore\DbTable;

/**
 * Class DbTableDataSource
 * @package rollun\datastore\DataSource
 */
class DbTableDataSource extends DbTable implements DataSourceInterface
{
    /**
     * @return array Return data of DataSource
     */
    public function getAll()
    {
        return $this->query(new Query());
    }
}
