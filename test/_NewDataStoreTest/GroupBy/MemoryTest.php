<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 12.04.18
 * Time: 2:25 PM
 */

namespace rollun\test\datastore\DataStore\GroupBy;


use rollun\datastore\DataStore\Memory;
use rollun\test\datastore\DataStore\AbstractMemoryTest;
use rollun\test\datastore\DataStore\NewStyleAggregateDecoratorTrait;
use rollun\test\datastore\DataStore\OldStyleAggregateDecoratorTrait;

class MemoryTest extends AbstractMemoryTest
{
    use GroupByTestTrait;


}