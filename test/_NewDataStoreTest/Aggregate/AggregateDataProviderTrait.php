<?php


namespace rollun\test\datastore\DataStore\Aggregate;

use rollun\datastore\Rql\Node\AggregateFunctionNode;
use rollun\datastore\Rql\Node\GroupbyNode;
use Xiag\Rql\Parser\Node\LimitNode;

trait AggregateDataProviderTrait
{

    /**
     * Prepare datastore for initialized with transmitted data
     * @param array $data
     * @return void
     */
    abstract protected function setInitialData(array $data);

    /**
     * @param $filedName
     * @param $aggregateFunction
     * @return string
     */
    abstract protected function decorateAggregateField($filedName, $aggregateFunction);

    /**
     * Return dataStore Identifier field name
     * @return string
     */
    abstract protected function getDataStoreIdentifier();

    /**
     * @return array
     */
    private function getInitSimpleDataForDataStore() {
        return [
            [$this->getDataStoreIdentifier() => 0],
            [$this->getDataStoreIdentifier() => 1],
            [$this->getDataStoreIdentifier() => 2],
            [$this->getDataStoreIdentifier() => 3],
            [$this->getDataStoreIdentifier() => 4],
            [$this->getDataStoreIdentifier() => 5],
        ];
    }

    /**
     * DataProvider for testMaxSuccess
     * @return array
     */
    public function provideMaxSuccessData()
    {
        $this->setInitialData($this->getInitSimpleDataForDataStore());
        return [
            //Test with id
            [
                $this->getDataStoreIdentifier(),
                [[
                    $this->decorateAggregateField($this->getDataStoreIdentifier(), "max") => 5
                ]]
            ]
        ];
    }

    /**
     * DataProvider for testMinSuccess
     * @return array
     */
    public function provideMinSuccessData()
    {
        $this->setInitialData($this->getInitSimpleDataForDataStore());
        return [
            [
                $this->getDataStoreIdentifier(),
                [[
                    $this->decorateAggregateField($this->getDataStoreIdentifier(), "min") => 0
                ]]
            ]
        ];
    }

    /**
     * DataProvider for testAvgSuccess
     * @return array
     */
    public function provideAvgSuccessData()
    {
        $this->setInitialData($this->getInitSimpleDataForDataStore());
        return [
            [
                $this->getDataStoreIdentifier(),
                [[
                    $this->decorateAggregateField($this->getDataStoreIdentifier(), "avg") => 2.5
                ]]
            ]
        ];
    }

    /**
     * DataProvider for testCountSuccess
     * @return array
     */
    public function provideCountSuccessData()
    {
        $this->setInitialData($this->getInitSimpleDataForDataStore());
        return [
            [
                $this->getDataStoreIdentifier(),
                [[
                    $this->decorateAggregateField($this->getDataStoreIdentifier(), "count") => 6
                ]]
            ]
        ];
    }

    private static $FIELD_CLASSIFIER = "classifier";

    private static $FIELD_WEIGHT = "weight";

    private function getInitMixedDataForDataStore()
    {
        return [
            [$this->getDataStoreIdentifier() => 0, static::$FIELD_CLASSIFIER => "A", static::$FIELD_WEIGHT => 1],
            [$this->getDataStoreIdentifier() => 1, static::$FIELD_CLASSIFIER => "A", static::$FIELD_WEIGHT => 3],
            [$this->getDataStoreIdentifier() => 2, static::$FIELD_CLASSIFIER => "A", static::$FIELD_WEIGHT => 4.3],
            [$this->getDataStoreIdentifier() => 3, static::$FIELD_CLASSIFIER => "A", static::$FIELD_WEIGHT => 2.1],

            [$this->getDataStoreIdentifier() => 4, static::$FIELD_CLASSIFIER => "B", static::$FIELD_WEIGHT => 1.3],
            [$this->getDataStoreIdentifier() => 5, static::$FIELD_CLASSIFIER => "B", static::$FIELD_WEIGHT => 0.54],

            [$this->getDataStoreIdentifier() => 6, static::$FIELD_CLASSIFIER => "C", static::$FIELD_WEIGHT => -0.23],
            [$this->getDataStoreIdentifier() => 7, static::$FIELD_CLASSIFIER => "C", static::$FIELD_WEIGHT => 0.54],
            [$this->getDataStoreIdentifier() => 8, static::$FIELD_CLASSIFIER => "C", static::$FIELD_WEIGHT => 1.10],

            [$this->getDataStoreIdentifier() => 9, static::$FIELD_CLASSIFIER => "D", static::$FIELD_WEIGHT => 5.32],
        ];
    }

    /**
     * DataProvider for testAggregateWithLimitOffsetSuccess
     * @return array
     */
    public function provideAggregateWithLimitOffsetSuccessData()
    {
        $this->setInitialData($this->getInitMixedDataForDataStore());
        return [
            [
                new LimitNode(1),
                new AggregateFunctionNode("max", $this->getDataStoreIdentifier()),
                [[
                    $this->decorateAggregateField($this->getDataStoreIdentifier(), "max") => 9
                ]]
            ],
            [
                new LimitNode(5),
                new AggregateFunctionNode("max", $this->getDataStoreIdentifier()),
                [[
                    $this->decorateAggregateField($this->getDataStoreIdentifier(), "max") => 9
                ]]
            ],
            [
                new LimitNode(5, 1),
                new AggregateFunctionNode("max", $this->getDataStoreIdentifier()),
                []
            ],
            [
                new LimitNode(2, 4),
                new AggregateFunctionNode("max", $this->getDataStoreIdentifier()),
                []
            ]
        ];
    }

    /**
     * DataProvider for testAggregateWithGroupBySuccess
     * @return array
     */
    public function provideAggregateWithGroupBySuccessData()
    {
        $this->setInitialData($this->getInitMixedDataForDataStore());
        return [
            [
                new GroupbyNode([static::$FIELD_CLASSIFIER]),
                new AggregateFunctionNode("avg", static::$FIELD_WEIGHT),
                [
                    [$this->decorateAggregateField(static::$FIELD_WEIGHT, "avg") => 2.6],
                    [$this->decorateAggregateField(static::$FIELD_WEIGHT, "avg") => 0.92],
                    [$this->decorateAggregateField(static::$FIELD_WEIGHT, "avg") => 0.47],
                    [$this->decorateAggregateField(static::$FIELD_WEIGHT, "avg") => 5.32],
                ]
            ],
            [
                new GroupbyNode([static::$FIELD_CLASSIFIER]),
                new AggregateFunctionNode("min", static::$FIELD_WEIGHT),
                [
                    [$this->decorateAggregateField(static::$FIELD_WEIGHT, "min") => 1],
                    [$this->decorateAggregateField(static::$FIELD_WEIGHT, "min") => 0.54],
                    [$this->decorateAggregateField(static::$FIELD_WEIGHT, "min") => -0.23],
                    [$this->decorateAggregateField(static::$FIELD_WEIGHT, "min") => 5.32],
                ]
            ],
            [
                new GroupbyNode([static::$FIELD_CLASSIFIER]),
                new AggregateFunctionNode("max", static::$FIELD_WEIGHT),
                [
                    [$this->decorateAggregateField(static::$FIELD_WEIGHT, "max") => 4.3],
                    [$this->decorateAggregateField(static::$FIELD_WEIGHT, "max") => 1.3],
                    [$this->decorateAggregateField(static::$FIELD_WEIGHT, "max") => 1.10],
                    [$this->decorateAggregateField(static::$FIELD_WEIGHT, "max") => 5.32],
                ]
            ]
        ];
    }

    /**
     * DataProvider for testAggregateWithSelectGroupBySuccess
     * @return array
     */
    public function provideAggregateWithSelectGroupBySuccessData()
    {
        $this->setInitialData($this->getInitMixedDataForDataStore());
        return [
            [
                new GroupbyNode([static::$FIELD_CLASSIFIER]),
                new AggregateFunctionNode("avg", static::$FIELD_WEIGHT),
                [
                    [static::$FIELD_CLASSIFIER => "A", $this->decorateAggregateField(static::$FIELD_WEIGHT, "avg") => 2.6],
                    [static::$FIELD_CLASSIFIER => "B", $this->decorateAggregateField(static::$FIELD_WEIGHT, "avg") => 0.92],
                    [static::$FIELD_CLASSIFIER => "C", $this->decorateAggregateField(static::$FIELD_WEIGHT, "avg") => 0.47],
                    [static::$FIELD_CLASSIFIER => "D", $this->decorateAggregateField(static::$FIELD_WEIGHT, "avg") => 5.32],
                ]
            ],
            [
                new GroupbyNode([static::$FIELD_CLASSIFIER]),
                new AggregateFunctionNode("min", static::$FIELD_WEIGHT),
                [
                    [static::$FIELD_CLASSIFIER => "A", $this->decorateAggregateField(static::$FIELD_WEIGHT, "min") => 1],
                    [static::$FIELD_CLASSIFIER => "B", $this->decorateAggregateField(static::$FIELD_WEIGHT, "min") => 0.54],
                    [static::$FIELD_CLASSIFIER => "C", $this->decorateAggregateField(static::$FIELD_WEIGHT, "min") => -0.23],
                    [static::$FIELD_CLASSIFIER => "D", $this->decorateAggregateField(static::$FIELD_WEIGHT, "min") => 5.32],
                ]
            ],
            [
                new GroupbyNode([static::$FIELD_CLASSIFIER]),
                new AggregateFunctionNode("max", static::$FIELD_WEIGHT),
                [
                    [static::$FIELD_CLASSIFIER => "A", $this->decorateAggregateField(static::$FIELD_WEIGHT, "max") => 4.3],
                    [static::$FIELD_CLASSIFIER => "B", $this->decorateAggregateField(static::$FIELD_WEIGHT, "max") => 1.3],
                    [static::$FIELD_CLASSIFIER => "C", $this->decorateAggregateField(static::$FIELD_WEIGHT, "max") => 1.10],
                    [static::$FIELD_CLASSIFIER => "D", $this->decorateAggregateField(static::$FIELD_WEIGHT, "max") => 5.32],

                ]
            ]
        ];
    }

    /**
     * DataProvider for testAggregateWithLimitOffsetGroupBySuccess
     * @return array
     */
    public function provideAggregateWithSelectLimitOffsetGroupBySuccessData()
    {
        $this->setInitialData($this->getInitMixedDataForDataStore());
        return [
            [
                new LimitNode(2),
                new GroupbyNode([static::$FIELD_CLASSIFIER]),
                new AggregateFunctionNode("avg", static::$FIELD_WEIGHT),
                [
                    [static::$FIELD_CLASSIFIER => "A", $this->decorateAggregateField(static::$FIELD_WEIGHT, "avg") => 2.6],
                    [static::$FIELD_CLASSIFIER => "B", $this->decorateAggregateField(static::$FIELD_WEIGHT, "avg") => 0.92],
                ]
            ],
            [
                new LimitNode(2, 1),
                new GroupbyNode([static::$FIELD_CLASSIFIER]),
                new AggregateFunctionNode("min", static::$FIELD_WEIGHT),
                [
                    [static::$FIELD_CLASSIFIER => "B", $this->decorateAggregateField(static::$FIELD_WEIGHT, "min") => 0.54],
                    [static::$FIELD_CLASSIFIER => "C", $this->decorateAggregateField(static::$FIELD_WEIGHT, "min") => -0.23],
                ]
            ],
            [
                new LimitNode(1, 3),
                new GroupbyNode([static::$FIELD_CLASSIFIER]),
                new AggregateFunctionNode("max", static::$FIELD_WEIGHT),
                [
                    [static::$FIELD_CLASSIFIER => "D", $this->decorateAggregateField(static::$FIELD_WEIGHT, "max") => 5.32],
                ]
            ],
            [
                new LimitNode(5),
                new GroupbyNode([static::$FIELD_CLASSIFIER]),
                new AggregateFunctionNode("count", static::$FIELD_WEIGHT),
                [
                    [static::$FIELD_CLASSIFIER => "A", $this->decorateAggregateField(static::$FIELD_WEIGHT, "count") => 4],
                    [static::$FIELD_CLASSIFIER => "B", $this->decorateAggregateField(static::$FIELD_WEIGHT, "count") => 2],
                    [static::$FIELD_CLASSIFIER => "C", $this->decorateAggregateField(static::$FIELD_WEIGHT, "count") => 3],
                    [static::$FIELD_CLASSIFIER => "D", $this->decorateAggregateField(static::$FIELD_WEIGHT, "count") => 1],
                ]
            ]
        ];
    }

    /**
     * DataProvider for testAggregateWithSelectException
     * @return array
     */
    public function provideAggregateWithSelectExceptionData()
    {
        $this->setInitialData($this->getInitMixedDataForDataStore());
        return [
            [
                new AggregateFunctionNode("min", static::$FIELD_WEIGHT),
                [
                    $this->getDataStoreIdentifier(),
                ]
            ],
            [
                new AggregateFunctionNode("max", static::$FIELD_WEIGHT),
                [
                    static::$FIELD_CLASSIFIER,
                ]
            ],
            [
                new AggregateFunctionNode("max", static::$FIELD_WEIGHT),
                [
                    static::$FIELD_CLASSIFIER,
                    static::$FIELD_WEIGHT
                ]
            ]
        ];

    }
}