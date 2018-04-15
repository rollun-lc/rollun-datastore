<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 13.04.18
 * Time: 2:18 PM
 */

namespace rollun\test\datastore\DataStore\GroupBy;

use PHPUnit\Framework\Assert;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\datastore\Rql\Node\GroupbyNode;
use rollun\datastore\Rql\RqlQuery;
use rollun\test\datastore\DataStore\AbstractDataStoreTest;

trait GroupByTestTrait
{
    /**
     * @var DataStoresInterface
     */
    protected $object;

    /**
     * Data provider for testGroupByField
     * @return mixed
     */
    abstract function provideGroupByFieldData();

    /**
     * Data provider for testGroupByException
     * @return mixed
     */
    abstract function provideGroupByExceptionData();

    /**
     * @param array $groupByFields
     * @param $expectedResult
     * @dataProvider provideGroupByFieldData
     */
    public function testGroupByField(array $groupByFields, $expectedResult)
    {
        $query = new RqlQuery();
        $query->setGroupby(new GroupbyNode($groupByFields));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param array $groupByFields
     * @expectedException \rollun\datastore\DataStore\DataStoreException
     * @dataProvider provideGroupByExceptionData
     */
    public function testGroupByException(array $groupByFields)
    {
        $query = new RqlQuery();
        $query->setGroupby(new GroupbyNode($groupByFields));
        $this->object->query($query);
    }
}