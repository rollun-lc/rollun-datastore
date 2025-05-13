<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\Node;

use Xiag\Rql\Parser\Node\AbstractQueryNode;

class AggregateFunctionNode extends AbstractQueryNode implements \Stringable
{
    /**
     * @return string
     */
    public function getNodeName()
    {
        return $this->function;
    }

    /**
     * AggregateFunctionNode constructor.
     * @param $function
     * @param $field
     */
    public function __construct(private $function, private $field) {}

    /**
     * @return mixed
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * @return mixed
     */
    public function getField()
    {
        return $this->field;
    }

    public function __toString(): string
    {
        return sprintf("%s(%s)", $this->function, $this->field);
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
