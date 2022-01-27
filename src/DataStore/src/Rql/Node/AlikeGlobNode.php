<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\Node;

use Graviton\RqlParser\DataType\Glob;

class AlikeGlobNode extends AlikeNode
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
