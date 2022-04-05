<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\Cleaner;

use rollun\utils\Cleaner\CleaningValidator\CleaningValidatorInterface;
use rollun\utils\Cleaner\Cleaner as BaseCleaner;
use rollun\datastore\DataStore\Interfaces\DataStoreInterface;

/**
 * Class Cleaner
 * @package rollun\datastore\Cleaner
 */
class Cleaner extends BaseCleaner
{
    public function __construct(DataStoreInterface $datastore, CleaningValidatorInterface $cleaningValidator)
    {
        $cleanableList = new CleanableListAdapter($datastore);
        parent::__construct($cleanableList, $cleaningValidator);
    }
}
