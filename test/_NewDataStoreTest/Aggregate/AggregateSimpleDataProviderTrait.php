<?php


namespace rollun\test\datastore\DataStore\Aggregate;


trait AggregateSimpleDataProviderTrait
{

    /**
     * Prepare datastore for initialized with transmitted data
     * @param array $data
     * @return void
     */
    abstract protected function setInitialData(array $data);

    /**
     * @return string
     */
    abstract protected function getDataStoreIdentifier();

    /**
     * @param $filedName
     * @param $aggregateFunction
     * @return string
     */
    abstract protected function decorateAggregateField($filedName, $aggregateFunction);

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
}