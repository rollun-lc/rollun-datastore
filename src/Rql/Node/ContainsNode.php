<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 06.02.17
 * Time: 14:40
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
