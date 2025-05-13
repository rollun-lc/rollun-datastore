<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\Node\BinaryNode;

class EqfNode extends BinaryOperatorNodeAbstract
{
    public function getNodeName()
    {
        return 'eqf';
    }
}
