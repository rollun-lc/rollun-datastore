<?php


namespace rollun\test\datastore\DataStore\Aggregate;


use rollun\datastore\Rql\Node\AggregateFunctionNode;
use Xiag\Rql\Parser\Node\LimitNode;

trait AggregateMixedDataProviderTrait
{
    abstract function getDataStoreIdentifier();

    function getInitDataForDataStore() {
        return [
            [$this->getDataStoreIdentifier() => 1, ],
            [$this->getDataStoreIdentifier() => 2, ],
            [$this->getDataStoreIdentifier() => 3, ],
            [$this->getDataStoreIdentifier() => 4, ],
            [$this->getDataStoreIdentifier() => 5, ],
            [$this->getDataStoreIdentifier() => 6, ],
            [$this->getDataStoreIdentifier() => 7, ],
        ];
    }

    /**
     * DataProvider for testAggregateWithLimitOffsetSuccess
     * @return array
     */
     function provideAggregateWithLimitOffsetSuccessData() {
         return [
             [
                 new LimitNode(),
                 new AggregateFunctionNode(),
                 [

                 ]
             ]
         ];
     }

    /**
     * DataProvider for testAggregateWithGroupBySuccess
     * @return array
     */
     function provideAggregateWithGroupBySuccessData() {
         return [];
     }

    /**
     * DataProvider for testAggregateWithSelectGroupBySuccess
     * @return array
     */
     function provideAggregateWithSelectGroupBySuccessData() {
         return [];
     }

    /**
     * DataProvider for testAggregateWithLimitOffsetGroupBySuccess
     * @return array
     */
     function provideAggregateWithLimitOffsetGroupBySuccessData() {
         return [];
     }

    /**
     * DataProvider for testAggregateWithGroupByException
     * @return array
     */
     function provideAggregateWithGroupByExceptionData() {
         return [];
     }

    /**
     * DataProvider for testAggregateWithSelectException
     * @return array
     */
     function provideAggregateWithSelectExceptionData() {
         return [];
     }
}