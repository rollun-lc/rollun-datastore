<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\Node\BinaryNode;

use Graviton\RqlParser\Node\Query\AbstractComparisonOperatorNode;

abstract class BinaryOperatorNodeAbstract extends AbstractComparisonOperatorNode
{
    public function __construct(string $field)
    {
        $this->field = $field;
    }

    /**
     * @return string|void
     * @todo
     */
    public function toRql()
    {
        // TODO: Implement toRql() method.
    }
}
