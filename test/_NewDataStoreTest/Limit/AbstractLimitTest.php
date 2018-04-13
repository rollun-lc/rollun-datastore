<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 13.04.18
 * Time: 2:18 PM
 */

namespace rollun\test\datastore\DataStore\Limit;

use PHPUnit\Framework\Assert;
use rollun\test\datastore\DataStore\AbstractDataStoreTest;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Query;

/**
 * Class AbstractLimitTest
 * @package rollun\test\datastore\DataStore\Limit
 */
abstract class AbstractLimitTest extends AbstractDataStoreTest
{

    /**
     * DataProvider for testLimitOffsetInvalid
     * @return mixed
     */
    abstract public function provideLimitOffsetInvalidData();

    /**
     * DataProvider for testLimit
     * @return mixed
     */
    abstract public function provideLimitData();

    /**
     * DataProvider for testLimitOffset
     * @return mixed
     */
    abstract public function provideLimitOffsetData();


    /**
     * @param $limit
     * @param $expectedResult
     */
    public function testLimit($limit, $expectedResult)
    {
        $query = new Query();
        $query->setLimit(new LimitNode($limit));
        $result = $this->object->query($query);
        Assert::assertLessThanOrEqual($limit, count($expectedResult), "Expected data not valid. Must be les or equals then limit - $limit.");
        Assert::assertLessThanOrEqual($limit, count($result));
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param $limit
     * @param $offset
     * @param $expectedResult
     */
    public function testLimitOffset($limit, $offset, $expectedResult)
    {
        $query = new Query();
        $query->setLimit(new LimitNode($limit, $offset));
        $result = $this->object->query($query);
        Assert::assertLessThanOrEqual($limit, count($expectedResult), "Expected data not valid. Must be les or equals then limit - $limit.");
        Assert::assertLessThanOrEqual($limit, count($result));
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param $limit
     * @param $offset
     * @dataProvider provideLimitOffsetInvalidData
     * @expectedException \rollun\datastore\DataStore\DataStoreException
     */
    public function testLimitOffsetInvalid($limit, $offset)
    {
        $query = new Query();
        $query->setLimit(new LimitNode($limit, $offset));
        $this->object->query($query);
    }
}