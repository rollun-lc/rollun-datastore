<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\TableGateway\DbSql;

use Laminas\Db\Sql\Exception\InvalidArgumentException;
use Laminas\Db\Sql\Sql;

class MultiInsertSql extends Sql
{
    public function insert($table = null)
    {
        if ($this->table !== null && $table !== null) {
            throw new InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }

        return new MultiInsert(($table) ?: $this->table);
    }
}
