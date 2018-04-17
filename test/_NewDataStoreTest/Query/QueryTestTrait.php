<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 13.04.18
 * Time: 5:02 PM
 */

namespace rollun\test\datastore\DataStore\Query;


use PHPUnit\Framework\Assert;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Xiag\Rql\Parser\DataType\Glob;
use Xiag\Rql\Parser\Node\AbstractQueryNode;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\InNode;
use Xiag\Rql\Parser\Node\Query\ArrayOperator\OutNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\AndNode;
use Xiag\Rql\Parser\Node\Query\LogicOperator\OrNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\EqNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\GtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LikeNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\LtNode;
use Xiag\Rql\Parser\Node\Query\ScalarOperator\NeNode;
use Xiag\Rql\Parser\Query;

trait QueryTestTrait
{

    /*public function testMatchSuccess($field, $value, $expectedResult) {}*/
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
     * Return dataStore Tex field name.
     * @return string
     */
    final private function getTextFieldName()
    {
        return "text";
    }

    /**
     * Return dataStore Nam field name.
     * @return string
     */
    final private function getNameFieldName()
    {
        return "name";
    }

    /**
     * Return dataStore Sur field name.
     * @return string
     */
    final private function getSurnameFieldName()
    {
        return "surname";
    }

    /**
     * Return dataStore Age field name.
     * @return string
     */
    final private function getAgeFieldName()
    {
        return "age";
    }

    /**
     * TODO: move in another trait.
     * @return array
     */
    final private function getInitDataForDataStore()
    {
        return [
            [
                $this->getDataStoreIdentifier() => 0,
                $this->getNameFieldName() => "name0",
                $this->getSurnameFieldName() => "surname0",
                $this->getAgeFieldName() => 20,
                $this->getTextFieldName() => ""
            ],
            [
                $this->getDataStoreIdentifier() => 1,
                $this->getNameFieldName() => "name1",
                $this->getSurnameFieldName() => "surname1",
                $this->getAgeFieldName() => 21,
                $this->getTextFieldName() => "text"
            ],
            [
                $this->getDataStoreIdentifier() => 2,
                $this->getNameFieldName() => "name2",
                $this->getSurnameFieldName() => "surname2",
                $this->getAgeFieldName() => 22,
                $this->getTextFieldName() => "text"
            ],
            [
                $this->getDataStoreIdentifier() => 3,
                $this->getNameFieldName() => "name1",
                $this->getSurnameFieldName() => "025",
                $this->getAgeFieldName() => 12,
                $this->getTextFieldName() => "123.43"
            ],
        ];
    }

    /**
     * Data provider for testEqSuccess
     * @return array
     */
    public function provideEqSuccessData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "filter by id" => [
                $this->getDataStoreIdentifier(), 0, [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                ]
            ],
            "filter by text" => [
                $this->getTextFieldName(), "text", [
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
            "filter by string surname" => [
                $this->getSurnameFieldName(), "025", [

                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "filter by name" => [
                $this->getNameFieldName(), "name1",
                [
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ]
        ];
    }

    /**
     * Data provider for testNeSuccess
     * @return array
     */
    public function provideNeSuccessData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "filter by id" => [
                $this->getDataStoreIdentifier(), 0, [
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "filter by text" => [
                $this->getTextFieldName(), "text", [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "filter by string surname" => [
                $this->getSurnameFieldName(), "025", [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
            "filter by name" => [
                $this->getNameFieldName(), "name1", [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ]
        ];
    }

    /**
     * Data provider for testGeSuccess
     * @return array
     */
    public function provideGeSuccessData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "grate then or equals age" => [
                $this->getAgeFieldName(), 21, [
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
            "grate then or equals id" => [
                $this->getDataStoreIdentifier(), 2, [
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "grate then or equals numeric surname" => [
                $this->getSurnameFieldName(), 25, [
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "grate then or equals name" => [
                $this->getNameFieldName(), "name1", [
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "grate then or equals numeric text" => [
                $this->getTextFieldName(), "123.43", [
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ]
        ];
    }

    /**
     * Data provider for testGtSuccess
     * @return array
     */
    public function provideGtSuccessData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "grate then age" => [
                $this->getAgeFieldName(), 21, [
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
            "grate then id" => [
                $this->getDataStoreIdentifier(), 2, [
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "grate then numeric surname" => [
                $this->getSurnameFieldName(), 24, [
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "grate then name" => [
                $this->getNameFieldName(), "name1", [
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
            "grate then numeric text" => [
                $this->getTextFieldName(), "123.42", [
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ]
        ];
    }

    /**
     * Data provider for testLeSuccess
     * @return array
     */
    public function provideLeSuccessData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "grate then or equals age" => [
                $this->getAgeFieldName(), 21, [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "grate then or equals id" => [
                $this->getDataStoreIdentifier(), 2, [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
            "grate then or equals numeric surname" => [
                $this->getSurnameFieldName(), 25, [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "grate then or equals name" => [
                $this->getNameFieldName(), "name1", [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "grate then or equals numeric text" => [
                $this->getTextFieldName(), "123.43", [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ]
        ];
    }

    /**
     * Data provider for testLtSuccess
     * @return array
     */
    public function provideLtSuccessData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "grate then or equals age" => [
                $this->getAgeFieldName(), 21, [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "grate then or equals id" => [
                $this->getDataStoreIdentifier(), 2, [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
            "grate then or equals numeric surname" => [
                $this->getSurnameFieldName(), 26, [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "grate then or equals name" => [
                $this->getNameFieldName(), "name1", [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                ]
            ],
            "grate then or equals numeric text" => [
                $this->getTextFieldName(), "123.44", [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ]
        ];
    }

    /**
     * Data provider for testInSuccess
     * @return array
     */
    public function provideInSuccessData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "in name" => [
                $this->getNameFieldName(), ["name1", "name0"],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "in surname with numeric" => [
                $this->getSurnameFieldName(), [25, 1],
                [
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "in surname with numeric string" => [
                $this->getSurnameFieldName(), ["025", 1],
                [
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "in age" => [
                $this->getAgeFieldName(), [22, 12],
                [
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "in text  with numeric" => [
                $this->getTextFieldName(), ["123.43", ""],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
        ];
    }

    /**
     * Data provider for testOutSuccess
     * @return array
     */
    public function provideOutSuccessData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "in name" => [
                $this->getNameFieldName(), ["name1", "name0"],
                [
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
            "in surname with numeric" => [
                $this->getSurnameFieldName(), [25, 1],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
            "in age" => [
                $this->getAgeFieldName(), [22, 12],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
            "in text  with numeric" => [
                $this->getTextFieldName(), ["123.43", ""],
                [
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
        ];
    }

    /**
     * Data provider for testLikeSuccess
     * @return array
     */
    public function provideLikeSuccessData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
             [
                 $this->getNameFieldName(), new Glob("name?"),
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
             ],
            [
                $this->getSurnameFieldName(), new Glob("s*0*"),
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                ]
            ],
            [
                $this->getAgeFieldName(), new Glob("2?"),
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
            [
                $this->getTextFieldName(), new Glob("*"),
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
        ];
    }

    /**
     * Data provider for testAndSuccess
     * @return array
     */
    public function provideAndSuccessData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "select with name and age grate then or equals " => [
                [new EqNode($this->getNameFieldName(), "name1"), new GeNode($this->getAgeFieldName(), 20)],
                [
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
            "select with name not eq " => [
                [new NeNode($this->getNameFieldName(), "name1"), new LikeNode($this->getTextFieldName(), new Glob("t*"))],
                [
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
        ];
    }

    /**
     * Data provider for testOrSuccess
     * @return array
     */
    public function provideOrSuccessData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "select with name or age grate then " => [
                [new EqNode($this->getNameFieldName(), "name1"), new GtNode($this->getAgeFieldName(), 20)],
                [
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 3,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "025",
                        $this->getAgeFieldName() => 12,
                        $this->getTextFieldName() => "123.43"
                    ],
                ]
            ],
            "select with name not equals or  " => [
                [new NeNode($this->getNameFieldName(), "name1"), new LikeNode($this->getTextFieldName(), new Glob("t*"))],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => ""
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text"
                    ],
                ]
            ],
        ];
    }

    /**
     * Data provider for testMixedSuccess
     * @return array
     */
    public function provideMixedSuccessData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [

        ];
    }

    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideEqSuccessData
     */
    public function testEqSuccess($field, $value, $expectedResult)
    {
        $query = new Query();
        $query->setQuery(new EqNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }


    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideNeSuccessData
     */
    public function testNeSuccess($field, $value, $expectedResult)
    {
        $query = new Query();
        $query->setQuery(new NeNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }


    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideGeSuccessData
     */
    public function testGeSuccess($field, $value, $expectedResult)
    {
        $query = new Query();
        $query->setQuery(new GeNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }


    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideGtSuccessData
     */
    public function testGtSuccess($field, $value, $expectedResult)
    {
        $query = new Query();
        $query->setQuery(new GtNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }


    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideLeSuccessData
     */
    public function testLeSuccess($field, $value, $expectedResult)
    {
        $query = new Query();
        $query->setQuery(new LeNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideLtSuccessData
     */
    public function testLtSuccess($field, $value, $expectedResult)
    {
        $query = new Query();
        $query->setQuery(new LtNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param $field
     * @param $values
     * @param $expectedResult
     * @dataProvider provideInSuccessData
     */
    public function testInSuccess($field, array $values, $expectedResult)
    {
        $query = new Query();
        $query->setQuery(new InNode($field, $values));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param $field
     * @param $values
     * @param $expectedResult
     * @dataProvider provideOutSuccessData
     */
    public function testOutSuccess($field, array $values, $expectedResult)
    {
        $query = new Query();
        $query->setQuery(new OutNode($field, $values));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param $field
     * @param $value
     * @param $expectedResult
     * @dataProvider provideLikeSuccessData
     */
    public function testLikeSuccess($field, $value, $expectedResult)
    {
        $query = new Query();
        $query->setQuery(new LikeNode($field, $value));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param array $nodes
     * @param $expectedResult
     * @dataProvider provideAndSuccessData
     */
    public function testAndSuccess(array $nodes, $expectedResult)
    {
        $query = new Query();
        $query->setQuery(new AndNode($nodes));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param array $nodes
     * @param $expectedResult
     * @dataProvider provideOrSuccessData
     */
    public function testOrSuccess(array $nodes, $expectedResult)
    {
        $query = new Query();
        $query->setQuery(new OrNode($nodes));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param AbstractQueryNode $node
     * @param $expectedResult
     * @dataProvider provideMixedSuccessData
     */
    public function testMixedSuccess(AbstractQueryNode $node, $expectedResult)
    {
        $query = new Query();
        $query->setQuery($node);
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }
}