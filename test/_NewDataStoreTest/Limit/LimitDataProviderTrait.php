<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 15.04.18
 * Time: 11:38 AM
 */

namespace rollun\test\datastore\DataStore\Limit;


trait LimitDataProviderTrait
{

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
                    [$this->getDataStoreIdentifier() => 1]
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
}