<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15.05.17
 * Time: 17:47
 */

namespace rollun\datastore\DataStore\Traits;


use rollun\datastore\DataStore\DataStoreException;
use Xiag\Rql\Parser\Query;

trait NoSupportQueryTrait
{
    /**
     * {@inheritdoc}
     * http://www.simplecoding.org/sortirovka-v-mysql-neskolko-redko-ispolzuemyx-vozmozhnostej.html
     * http://ru.php.net/manual/ru/function.usort.php
     * @param Query $query
     * @return array[] fo items or [] if not any
     */
    public function query(Query $query)
    {
        throw new DataStoreException("Method don't support.");
    }
}