<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 07.02.17
 * Time: 12:18
 */

namespace rollun\datastore\Rql\Node;

use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LikeNode;

class LikeGlobNode extends LikeNode
{
    /**
     * @param string $field
     * @param mixed $value
     */
    public function __construct($field, $value)
    {
        $value = $value instanceof Glob ? $value : new Glob($value);
        parent::__construct($field, $value);
    }
    /**
     * @param mixed $value
     * @return void
     */
    public function setValue($value)
    {
        $this->value = $value instanceof Glob ? $value : new Glob($value);
    }
}
