<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataStore\Interfaces;

use Xiag\Rql\Parser\Query;

/**
 * Read Interface for DataStores
 *
 * @see http://en.wikipedia.org/wiki/Create,_read,_update_and_delete
 * @category   rest
 * @package    zaboy
 */
interface ReadInterface extends \Countable, \IteratorAggregate
{
    /**
     * Default identifier
     *
     * @see getIdentifier()
     */
    public const DEF_ID = 'id';

    /**
     * Use it in limit section in query if need
     */
    public const LIMIT_INFINITY = 2147483647;

    /**
     * Return primary key identifier
     *
     * Return 'id' by default
     *
     * @see DEF_ID
     * @return string 'id' by default
     */
    public function getIdentifier();

    /**
     * Return Item by 'id'
     *
     * Method return null if item with that id is absent.
     * Format of item:
     * [
     *      'id' => 123,
     *      field1' => 'value1',
     *      // ...
     * ]
     *
     * @param int|string $id PrimaryKey
     * @return array|null
     */
    public function read($id);

    /**
     * Return true if item with that 'id' is present.
     *
     * @param int|string $id PrimaryKey
     * @return bool
     */
    public function has($id);

    /**
     * Return items by criteria with mapping, sorting and paging
     *
     * Example:
     * <code>
     *  $query = new \Xiag\Rql\Parser\Query();
     *  $eqNode = new \Xiag\Rql\Parser\Node\ScalarOperator\EqNode(
     *      'fString', 'val2'
     *  );
     *  $query->setQuery($eqNode);
     *  $sortNode = new \Xiag\Rql\Parser\Node\Node\SortNode(['id' => '1']);
     *  $query->setSort($sortNode);
     *  $selectNode = new \Xiag\Rql\Parser\Node\Node\SelectNode(['fFloat']);
     *  $query->setSelect($selectNode);
     *  $limitNode = new \Xiag\Rql\Parser\Node\Node\LimitNode(2, 1);
     *  $query->setLimit($limitNode);
     *  $queryArray = $this->object->query($query);
     * </code>
     *
     *
     * ORDER
     * http://www.simplecoding.org/sortirovka-v-mysql-neskolko-redko-ispolzuemyx-vozmozhnostej.html
     * http://ru.php.net/manual/ru/function.usort.php
     *
     * @param Query $query
     * @return array[] of items or [] if not any
     */
    public function query(Query $query);
}
