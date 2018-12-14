<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Interfaces;

use Xiag\Rql\Parser\Query;

/**
 * Interface SqlQueryGetterInterface
 * @package rollun\datastore\DataStore\Interfaces
 */
interface SqlQueryGetterInterface
{
    public function getSqlQuery(Query $query);
}
