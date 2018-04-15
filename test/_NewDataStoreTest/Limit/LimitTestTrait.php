<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 13.04.18
 * Time: 2:18 PM
 */

namespace rollun\test\datastore\DataStore\Limit;

use PHPUnit\Framework\Assert;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use rollun\test\datastore\DataStore\AbstractDataStoreTest;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Query;

/**
 * Class AbstractLimitTest
 * @package rollun\test\datastore\DataStore\Limit
 */
trait LimitTestTrait
{
    /**
     * @var DataStoresInterface
     */
    protected $object;

    /**
     * Prepare datastore for initialized with transmitted data
     * @param array $data
     * @return void
     */
    abstract protected function setInitialData(array $data);


    /**
     * Return dataStore Identifier field name
     * @return string
     */
    abstract protected function getDataStoreIdentifier();

    /**
     * TODO: move in another trait.
     * @return array
     */
    final private function getInitDataForDataStore()
    {
        return [
            [$this->getDataStoreIdentifier() => 0],
            [$this->getDataStoreIdentifier() => 1],
            [$this->getDataStoreIdentifier() => 2],
            [$this->getDataStoreIdentifier() => 3],
            [$this->getDataStoreIdentifier() => 4],
            [$this->getDataStoreIdentifier() => 5],
            [$this->getDataStoreIdentifier() => 6],
            [$this->getDataStoreIdentifier() => 7],
            [$this->getDataStoreIdentifier() => 8],
            [$this->getDataStoreIdentifier() => 9],
        ];
    }

    /**
     * DataProvider for testLimitOffsetInvalid
     * @return mixed
     */
    public function provideLimitOffsetInvalidData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "zero limit" => [
                -1,
                0,
            ],
            "zero offset" => [
                1,
                -1,
            ],
            "zero limit and offset" => [
                -1,
                -1,
            ],
        ];
    }

    /**
     * DataProvider for testLimit
     * @return mixed
     */
    public function provideLimitData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            [
                1,
                [
                    [$this->getDataStoreIdentifier() => 0]
                ]
            ],
            [
                3,
                [
                    [$this->getDataStoreIdentifier() => 0],
                    [$this->getDataStoreIdentifier() => 1],
                    [$this->getDataStoreIdentifier() => 2],
                ],
            ],
            [
                0,
                [],
            ],
            [
                10,
                [
                    [$this->getDataStoreIdentifier() => 0],
                    [$this->getDataStoreIdentifier() => 1],
                    [$this->getDataStoreIdentifier() => 2],
                    [$this->getDataStoreIdentifier() => 3],
                    [$this->getDataStoreIdentifier() => 4],
                    [$this->getDataStoreIdentifier() => 5],
                    [$this->getDataStoreIdentifier() => 6],
                    [$this->getDataStoreIdentifier() => 7],
                    [$this->getDataStoreIdentifier() => 8],
                    [$this->getDataStoreIdentifier() => 9],
                ],
            ],
            [
                15,
                [
                    [$this->getDataStoreIdentifier() => 0],
                    [$this->getDataStoreIdentifier() => 1],
                    [$this->getDataStoreIdentifier() => 2],
                    [$this->getDataStoreIdentifier() => 3],
                    [$this->getDataStoreIdentifier() => 4],
                    [$this->getDataStoreIdentifier() => 5],
                    [$this->getDataStoreIdentifier() => 6],
                    [$this->getDataStoreIdentifier() => 7],
                    [$this->getDataStoreIdentifier() => 8],
                    [$this->getDataStoreIdentifier() => 9],
                ],
            ],
        ];
    }

    /**
     * DataProvider for testLimitOffset
     * @return mixed
     */
    public function provideLimitOffsetData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "offset zero" => [
                1,
                0,
                [
                    [$this->getDataStoreIdentifier() => 0]
                ]
            ],
            [
                1,
                1,
                [
                    [$this->getDataStoreIdentifier() => 1]
                ]
            ],
            "one 'shift' with offset" =>[
                3,
                1,
                [
                    [$this->getDataStoreIdentifier() => 1],
                    [$this->getDataStoreIdentifier() => 2],
                    [$this->getDataStoreIdentifier() => 3],
                ],
            ],
            "offset our data range " => [1, 10, [],],
            "zero limit and offset" => [0, 0, [],
            ],
            [
                10,
                5,
                [
                    [$this->getDataStoreIdentifier() => 5],
                    [$this->getDataStoreIdentifier() => 6],
                    [$this->getDataStoreIdentifier() => 7],
                    [$this->getDataStoreIdentifier() => 8],
                    [$this->getDataStoreIdentifier() => 9],
                ],
            ],
        ];
    }


    /**
     * @param $limit
     * @param $expectedResult
     * @dataProvider provideLimitData
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
     * @dataProvider provideLimitOffsetData
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