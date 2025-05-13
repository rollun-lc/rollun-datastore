<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Interfaces;

/**
 * Interface TreeReadInterface
 * @package rollun\datastore\DataStore\Interfaces
 */
interface TreeReadInterface extends ReadInterface
{
    /**
     * @return array|object|null
     */
    public function getRootCollection();

    /**
     * @param mixed
     * @return array|object|null
     */
    public function getParent($id);

    /**
     * @param mixed
     * @return array|object ArrayAccess,Traversable,Countable
     */
    public function getChildren($id);

    /**
     * @param mixed
     * @return bool
     */
    public function mayHaveChildren($id);

    /**
     * IT's true if $ancestor is (sub)parent for $id)
     *
     * @param mixed $id possibly ancestor
     * @param mixed $descendantId possibly descendant
     * @return bool
     */
    public function isAncestor($id, $descendantId);
}
