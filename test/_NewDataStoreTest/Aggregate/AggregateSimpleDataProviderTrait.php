<?php


namespace rollun\test\datastore\DataStore\Aggregate;


trait AggregateSimpleDataProviderTrait
{
    /**
     * @return string
     */
    abstract function getIdColumn();

    /**
     * @param $filedName
     * @param $aggregateFunction
     * @return string
     */
    abstract function decorateAggregateField($filedName, $aggregateFunction);

    /**
     * @return array
     */
    function getDataProviderInitData() {
        return [
            [$this->getIdColumn() => 0],
            [$this->getIdColumn() => 1],
            [$this->getIdColumn() => 2],
            [$this->getIdColumn() => 3],
            [$this->getIdColumn() => 4],
            [$this->getIdColumn() => 5],
        ];
    }

    /**
     * DataProvider for testMaxSuccess
     * @return array
     */
    function provideMaxSuccessData()
    {
        return [
            //Test with id
            [
                $this->getIdColumn(),
                [[
                    $this->decorateAggregateField($this->getIdColumn(), "max") => 5
                ]]
            ]
        ];
    }

    /**
     * DataProvider for testMinSuccess
     * @return array
     */
    function provideMinSuccessData()
    {
        return [
            [
                $this->getIdColumn(),
                [[
                    $this->decorateAggregateField($this->getIdColumn(), "min") => 0
                ]]
            ]
        ];
    }

    /**
     * DataProvider for testAvgSuccess
     * @return array
     */
    function provideAvgSuccessData()
    {
        return [
            [
                $this->getIdColumn(),
                [[
                    $this->decorateAggregateField($this->getIdColumn(), "avg") => 2.5
                ]]
            ]
        ];
    }

    /**
     * DataProvider for testCountSuccess
     * @return array
     */
    function provideCountSuccessData()
    {
        return [
            [
                $this->getIdColumn(),
                [[
                    $this->decorateAggregateField($this->getIdColumn(), "count") => 6
                ]]
            ]
        ];
    }
}