<?php

namespace rollun\datastore\Cleaner;

use rollun\utils\Cleaner\CleaningValidator\CleaningValidatorInterface;
use rollun\utils\Cleaner\Cleaner as BaseCleaner;
use rollun\datastore\Cleaner\CleanableListAdapter;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;

class Cleaner extends BaseCleaner
{

    public function __construct(DataStoresInterface $datastore, CleaningValidatorInterface $cleaningValidator)
    {
        $cleanableList = new CleanableListAdapter($datastore);
        parent::__construct($cleanableList, $cleaningValidator);
    }

}
