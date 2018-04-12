<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 12.04.18
 * Time: 11:17 AM
 */

namespace rollun\test\datastore\DataStore;


trait OldStyleAggregateDecorator
{
    /**
     * @param string $filedName
     * @param $aggregateFunction
     * @return string
     */
    protected function decorateAggregateField($filedName, $aggregateFunction)
    {
        //return "{$aggregateFunction}({$filedName})";
        return "{$filedName}->{$aggregateFunction}";
    }
}