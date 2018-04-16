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
use Xiag\Rql\Parser\Node\SelectNode;

trait GroupByTestTrait
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
     * Return dataStore field name
     * @return string
     */
    final private function getCategoryFieldName()
    {
        return "category";
    }

    /**
     * Return dataStore field name
     * @return string
     */
    final private function getSubCategoryFieldName()
    {
        return "subCategory";
    }

    /**
     * Return dataStore field name
     * @return string
     */
    final private function getWeightFieldName()
    {
        return "weight";
    }


    /**
     * @return array
     */
    final private function getInitDataForDataStore()
    {
        return [
            //[A]
            [
                $this->getDataStoreIdentifier() => 0,
                $this->getCategoryFieldName() => "A",
                $this->getSubCategoryFieldName() => "S_A",
                $this->getWeightFieldName() => 10,
            ],
            [
                $this->getDataStoreIdentifier() => 1,
                $this->getCategoryFieldName() => "A",
                $this->getSubCategoryFieldName() => "S_A",
                $this->getWeightFieldName() => 5,
            ],
            [
                $this->getDataStoreIdentifier() => 2,
                $this->getCategoryFieldName() => "A",
                $this->getSubCategoryFieldName() => "S_B",
                $this->getWeightFieldName() => 12,
            ],
            //[B]
            [
                $this->getDataStoreIdentifier() => 3,
                $this->getCategoryFieldName() => "B",
                $this->getSubCategoryFieldName() => "S_A",
                $this->getWeightFieldName() => 10,
            ],
            [
                $this->getDataStoreIdentifier() => 4,
                $this->getCategoryFieldName() => "B",
                $this->getSubCategoryFieldName() => "S_C",
                $this->getWeightFieldName() => 3,
            ],
            //[C]
            [
                $this->getDataStoreIdentifier() => 5,
                $this->getCategoryFieldName() => "C",
                $this->getSubCategoryFieldName() => "S_A",
                $this->getWeightFieldName() => 6,
            ],
            [
                $this->getDataStoreIdentifier() => 6,
                $this->getCategoryFieldName() => "C",
                $this->getSubCategoryFieldName() => "S_C",
                $this->getWeightFieldName() => 3,
            ],
            [
                $this->getDataStoreIdentifier() => 7,
                $this->getCategoryFieldName() => "C",
                $this->getSubCategoryFieldName() => "S_C",
                $this->getWeightFieldName() => 7,
            ],
            [
                $this->getDataStoreIdentifier() => 8,
                $this->getCategoryFieldName() => "C",
                $this->getSubCategoryFieldName() => "S_B",
                $this->getWeightFieldName() => 3,
            ],
            //[D]
            [
                $this->getDataStoreIdentifier() => 9,
                $this->getCategoryFieldName() => "D",
                $this->getSubCategoryFieldName() => "S_D",
                $this->getWeightFieldName() => 11,
            ],
        ];
    }

    /**
     * Data provider for testGroupByField
     * @return mixed
     */
    function provideGroupByFieldData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "group by id" => [
                [$this->getDataStoreIdentifier()],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getCategoryFieldName() => "A",
                        $this->getSubCategoryFieldName() => "S_A",
                        $this->getWeightFieldName() => 10,
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getCategoryFieldName() => "A",
                        $this->getSubCategoryFieldName() => "S_A",
                        $this->getWeightFieldName() => 5,
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getCategoryFieldName() => "A",
                        $this->getSubCategoryFieldName() => "S_B",
                        $this->getWeightFieldName() => 12,
                    ],
                    //[B]
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getCategoryFieldName() => "B",
                        $this->getSubCategoryFieldName() => "S_A",
                        $this->getWeightFieldName() => 10,
                    ],
                    [
                        $this->getDataStoreIdentifier() => 4,
                        $this->getCategoryFieldName() => "B",
                        $this->getSubCategoryFieldName() => "S_C",
                        $this->getWeightFieldName() => 3,
                    ],
                    //[C]
                    [
                        $this->getDataStoreIdentifier() => 5,
                        $this->getCategoryFieldName() => "C",
                        $this->getSubCategoryFieldName() => "S_A",
                        $this->getWeightFieldName() => 6,
                    ],
                    [
                        $this->getDataStoreIdentifier() => 6,
                        $this->getCategoryFieldName() => "C",
                        $this->getSubCategoryFieldName() => "S_C",
                        $this->getWeightFieldName() => 3,
                    ],
                    [
                        $this->getDataStoreIdentifier() => 7,
                        $this->getCategoryFieldName() => "C",
                        $this->getSubCategoryFieldName() => "S_C",
                        $this->getWeightFieldName() => 7,
                    ],
                    [
                        $this->getDataStoreIdentifier() => 8,
                        $this->getCategoryFieldName() => "C",
                        $this->getSubCategoryFieldName() => "S_B",
                        $this->getWeightFieldName() => 3,
                    ],
                    //[D]
                    [
                        $this->getDataStoreIdentifier() => 9,
                        $this->getCategoryFieldName() => "D",
                        $this->getSubCategoryFieldName() => "S_D",
                        $this->getWeightFieldName() => 11,
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testGroupByField
     * @return mixed
     */
    function provideGroupByWithSelectData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "group by id" => [
                [$this->getDataStoreIdentifier()],
                [
                    [$this->getDataStoreIdentifier() => 0,],
                    [$this->getDataStoreIdentifier() => 1,],
                    [$this->getDataStoreIdentifier() => 2,],
                    [$this->getDataStoreIdentifier() => 3,],
                    [$this->getDataStoreIdentifier() => 4,],
                    [$this->getDataStoreIdentifier() => 5,],
                    [$this->getDataStoreIdentifier() => 6,],
                    [$this->getDataStoreIdentifier() => 7,],
                    [$this->getDataStoreIdentifier() => 8,],
                    [$this->getDataStoreIdentifier() => 9,]

                ],
            ],
            "group by category" => [
                [$this->getCategoryFieldName()],
                [
                    [$this->getCategoryFieldName() => "A"],
                    [$this->getCategoryFieldName() => "B"],
                    [$this->getCategoryFieldName() => "C"],
                    [$this->getCategoryFieldName() => "D"],

                ],
            ],
            "group by sub category" => [
                [$this->getSubCategoryFieldName()],
                [
                    [$this->getSubCategoryFieldName() => "S_A"],
                    [$this->getSubCategoryFieldName() => "S_B"],
                    [$this->getSubCategoryFieldName() => "S_C"],
                    [$this->getSubCategoryFieldName() => "S_D"],
                ],
            ],
            "group by weight" => [
                [$this->getWeightFieldName()],
                [
                    [$this->getWeightFieldName() => 3,],
                    [$this->getWeightFieldName() => 5,],
                    [$this->getWeightFieldName() => 6,],
                    [$this->getWeightFieldName() => 7,],
                    [$this->getWeightFieldName() => 10,],
                    [$this->getWeightFieldName() => 11,],
                    [$this->getWeightFieldName() => 12,],
                ],
            ],
        ];
    }

    /**
     * Data provider for testGroupByException
     * @return mixed
     */
    function provideGroupByExceptionData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "get undefined field" => [
                ["undefined_field"]
            ],
            "group by category (in select has nonaggregated column)" => [
                [$this->getCategoryFieldName()],
            ],
            "group by sub category (in select has nonaggregated column)" => [
                [$this->getSubCategoryFieldName()],
            ],
            "group by weight (in select has nonaggregated column)" => [
                [$this->getWeightFieldName()],
            ],
        ];
    }

    /**
     * @param array $groupByFields
     * @param $expectedResult
     * @dataProvider provideGroupByWithSelectData
     */
    public function testGroupByWithSelect(array $groupByFields, $expectedResult)
    {
        $query = new RqlQuery();
        $query->setSelect(new SelectNode($groupByFields));
        $query->setGroupby(new GroupbyNode($groupByFields));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

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