<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\Node;

use Xiag\Rql\Parser\AbstractNode;

class GroupbyNode extends AbstractNode
{
    /**
     * GroupbyNode constructor.
     * @param array $fields
     */
    public function __construct(private array $fields)
    {
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return string
     */
    public function getNodeName()
    {
        return 'groupby';
    }

    public function toRql()
    {
        // TODO: Implement toRql() method.
    }
}
