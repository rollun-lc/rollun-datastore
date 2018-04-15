<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 13.04.18
 * Time: 6:29 PM
 */

namespace rollun\test\datastore\DataStore;


use rollun\datastore\Rql\Node\AggregateFunctionNode;

trait NewStyleAggregateDecoratorTrait
{
    /**
     * @param string $filedName
     * @param $aggregateFunction
     * @return string
     */
    protected function decorateAggregateField($filedName, $aggregateFunction)
    {
        return (new AggregateFunctionNode($aggregateFunction, $filedName))->__toString();
    }
}