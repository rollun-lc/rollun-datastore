<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql;

use Xiag\Rql\Parser\Parser;

class QueryParser extends Parser
{
    protected function createQueryBuilder()
    {
        return new RqlQueryBuilder();
    }
}
