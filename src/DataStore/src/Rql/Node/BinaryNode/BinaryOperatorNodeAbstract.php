<?php

namespace rollun\datastore\Rql\Node\BinaryNode;

use Xiag\Rql\Parser\Node\Query\AbstractComparisonOperatorNode;

abstract class BinaryOperatorNodeAbstract extends AbstractComparisonOperatorNode
{
    public function __construct(string $field)
    {
        $this->field = $field;
    }
}
