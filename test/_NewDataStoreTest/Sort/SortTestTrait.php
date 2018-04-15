<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 13.04.18
 * Time: 2:18 PM
 */

namespace rollun\test\datastore\DataStore\Sort;

use DateTime;
use PHPUnit\Framework\Assert;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Xiag\Rql\Parser\Node\SortNode;
use Xiag\Rql\Parser\Query;

trait SortTestTrait
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
    final private function getTextFieldName()
    {
        return "text";
    }

    /**
     * Return dataStore field name
     * @return string
     */
    final private function getNumFieldName()
    {
        return "num";
    }

    /**
     * Return dataStore field name
     * @return string
     */
    final private function getDateTimeFieldName()
    {
        return "dateTime";
    }

    /**
     * @return array
     */
    final private function getInitDataForDataStore()
    {
        return [
            [
                $this->getDataStoreIdentifier() => 1,
                $this->getTextFieldName() => "text1",
                $this->getNumFieldName() => -0.5,
                $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
            ],
            [
                $this->getDataStoreIdentifier() => 0,
                $this->getTextFieldName() => "text0",
                $this->getNumFieldName() => 20.3,
                $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
            ],
            [
                $this->getDataStoreIdentifier() => 2,
                $this->getTextFieldName() => "text1",
                $this->getNumFieldName() => 17,
                $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
            ],
        ];
    }

    /**
     * Data provider for testSortField
     * @return mixed
     */
    public function provideSortAscFieldData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "Sort by id" => [
                [$this->getDataStoreIdentifier()],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                ]
            ],
            "sort by text field" => [
                [$this->getTextFieldName()],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                ]
            ],
            "sort by num field" => [
                [$this->getNumFieldName()],
                [
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                ]
            ],
            "sort by dateTime field" => [
                [$this->getDateTimeFieldName()],
                [
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                ]
            ],
            "sort by text and num" => [
                [$this->getTextFieldName(), $this->getNumFieldName()],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                ],
            ],
            "sort by datetime, text, id" => [
                [$this->getDateTimeFieldName(), $this->getTextFieldName(), $this->getDataStoreIdentifier()],
                [
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                ]
            ],
            "empty sort" => [
                [],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testSortField
     * @return mixed
     */
    public function provideSortDescFieldData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "Sort by id" => [
                [$this->getDataStoreIdentifier()],
                [
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                ]
            ],
            "sort by text field" => [
                [$this->getTextFieldName()],
                [

                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                ]
            ],
            "sort by num field" => [
                [$this->getNumFieldName()],
                [

                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                ]
            ],
            "sort by dateTime field" => [
                [$this->getDateTimeFieldName()],
                [

                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                ]
            ],
            "sort by text and num" => [
                [$this->getTextFieldName(), $this->getNumFieldName()],
                [

                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                ],
            ],
            "sort by datetime, text, id" => [
                [$this->getDateTimeFieldName(), $this->getTextFieldName(), $this->getDataStoreIdentifier()],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                ]
            ],
            "empty sort" => [
                [],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getTextFieldName() => "text0",
                        $this->getNumFieldName() => 20.3,
                        $this->getDateTimeFieldName() => '2018-04-13 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => -0.5,
                        $this->getDateTimeFieldName() => '2018-04-11 08:53:10',
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getTextFieldName() => "text1",
                        $this->getNumFieldName() => 17,
                        $this->getDateTimeFieldName() => '2018-03-13 08:53:10',
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testSortException
     * @return mixed
     */
    public function provideSortExceptionData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "sort by undefined field" => [["undefined_field" => SortNode::SORT_DESC]],
            "Sort with undefined type" => [["undefined_field" => -5]],
        ];
    }

    /**
     * @param array $fields
     * @param $expectedResult
     * @dataProvider provideSortAscFieldData
     */
    public function testSortAscField(array $fields, $expectedResult)
    {
        $query = new Query();
        $sortFields = array_combine($fields,
            array_fill(0, count($fields), SortNode::SORT_ASC)
        );
        $query->setSort(new SortNode($sortFields));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param array $fields
     * @param $expectedResult
     * @dataProvider provideSortDescFieldData
     */
    public function testSortDescField(array $fields, $expectedResult)
    {
        $query = new Query();
        $sortFields = array_combine($fields,
            array_fill(0, count($fields), SortNode::SORT_DESC)
        );
        $query->setSort(new SortNode($sortFields));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param array $fields
     * @expectedException \rollun\datastore\DataStore\DataStoreException
     * @dataProvider provideSortExceptionData
     */
    public function testSortException(array $fields)
    {
        $query = new Query();
        $query->setSort(new SortNode($fields));
        $this->object->query($query);
    }
}