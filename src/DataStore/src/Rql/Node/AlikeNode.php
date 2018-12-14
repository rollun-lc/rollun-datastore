<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Rql\Node;

use Xiag\Rql\Parser\Node\Query\AbstractScalarOperatorNode;

class AlikeNode extends AbstractScalarOperatorNode
{
    /**
     * @inheritdoc
     */
    public function getNodeName()
    {
        return 'alike';
    }
}
