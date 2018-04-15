<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 13.04.18
 * Time: 2:18 PM
 */

namespace rollun\test\datastore\DataStore\Select;

use PHPUnit\Framework\Assert;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Xiag\Rql\Parser\Node\SelectNode;
use Xiag\Rql\Parser\Query;

trait SelectTestTrait
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
                $this->getTextFieldName() => "text0"
            ],
            [
                $this->getDataStoreIdentifier() => 1,
                $this->getNameFieldName() => "name1",
                $this->getSurnameFieldName() => "surname1",
                $this->getAgeFieldName() => 21,
                $this->getTextFieldName() => "text1"
            ],
            [
                $this->getDataStoreIdentifier() => 2,
                $this->getNameFieldName() => "name2",
                $this->getSurnameFieldName() => "surname2",
                $this->getAgeFieldName() => 22,
                $this->getTextFieldName() => "text2"
            ],
        ];
    }

    /**
     * DataProvider testSelectSuccess
     * @return mixed
     */
    public function provideSelectSuccessData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "Select id" => [
                [$this->getDataStoreIdentifier()],
                [
                    [$this->getDataStoreIdentifier() => 0],
                    [$this->getDataStoreIdentifier() => 1],
                    [$this->getDataStoreIdentifier() => 2]
                ]
            ],
            "Select id and name" => [
                [$this->getDataStoreIdentifier(), $this->getNameFieldName()],
                [
                    [$this->getDataStoreIdentifier() => 0, $this->getNameFieldName() => "name0",],
                    [$this->getDataStoreIdentifier() => 1, $this->getNameFieldName() => "name1",],
                    [$this->getDataStoreIdentifier() => 2, $this->getNameFieldName() => "name2",]
                ]
            ],
            "select all fields" => [
                [
                    $this->getDataStoreIdentifier(),
                    $this->getNameFieldName(),
                    $this->getSurnameFieldName(),
                    $this->getAgeFieldName(),
                    $this->getTextFieldName(),
                ],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => "text0"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text1"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text2"
                    ],
                ]
            ],
            "empty select" => [
                [],
                [
                    [
                        $this->getDataStoreIdentifier() => 0,
                        $this->getNameFieldName() => "name0",
                        $this->getSurnameFieldName() => "surname0",
                        $this->getAgeFieldName() => 20,
                        $this->getTextFieldName() => "text0"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 1,
                        $this->getNameFieldName() => "name1",
                        $this->getSurnameFieldName() => "surname1",
                        $this->getAgeFieldName() => 21,
                        $this->getTextFieldName() => "text1"
                    ],
                    [
                        $this->getDataStoreIdentifier() => 2,
                        $this->getNameFieldName() => "name2",
                        $this->getSurnameFieldName() => "surname2",
                        $this->getAgeFieldName() => 22,
                        $this->getTextFieldName() => "text2"
                    ],
                ]
            ]
        ];
    }

    /**
     * DataProvider testSelectUndefinedException
     * @return mixed
     */
    public function provideSelectUndefinedExceptionData()
    {
        $this->setInitialData($this->getInitDataForDataStore());
        return [
            "select undefined field" => [
                ["undefined_field"]
            ],
            "Select value in field " => [
                [1],
            ],
        ];
    }

    /**
     * @param array $fields
     * @param array $expectedResult
     * @dataProvider provideSelectSuccessData
     */
    public function testSelectSuccess(array $fields, array $expectedResult)
    {
        $query = new Query();
        $query->setSelect(new SelectNode($fields));
        $result = $this->object->query($query);
        Assert::assertEquals($expectedResult, $result);
    }

    /**
     * @param array $fields
     * @dataProvider provideSelectUndefinedExceptionData
     * @expectedException \rollun\datastore\DataStore\DataStoreException
     */
    public function testSelectUndefinedException(array $fields)
    {
        $query = new Query();
        $query->setSelect(new SelectNode($fields));
        $this->object->query($query);
    }
}