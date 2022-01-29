<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\Node;

use Xiag\Rql\Parser\Node\Query\AbstractScalarOperatorNode;

class ContainsNode extends AbstractScalarOperatorNode
{

    /**
     * @return string
     */
    public function getNodeName()
    {
        return 'contains';
    }
}
