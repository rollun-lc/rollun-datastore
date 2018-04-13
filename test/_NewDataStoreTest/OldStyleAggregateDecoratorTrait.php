<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 12.04.18
 * Time: 11:17 AM
 */

namespace rollun\test\datastore\DataStore;


trait OldStyleAggregateDecoratorTrait
{
    /**
     * @param string $filedName
     * @param $aggregateFunction
     * @return string
     */
    protected function decorateAggregateField($filedName, $aggregateFunction)
    {
        return "{$filedName}->{$aggregateFunction}";
    }
}