<?php

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
