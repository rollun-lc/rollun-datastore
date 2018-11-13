<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 14.01.17
 * Time: 10:15 AM
 */

namespace rollun\datastore\Rql\Node;


use Xiag\Rql\Parser\AbstractNode;

class GroupByNode extends AbstractNode
{
    private $fields;

    /**
     * GroupbyNode constructor.
     * @param array $fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
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
}
