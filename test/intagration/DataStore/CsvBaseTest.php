<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\intagration\DataStore;

use rollun\datastore\DataStore\CsvBase;
use rollun\datastore\DataStore\DataStoreAbstract;
//use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

class CsvBaseTest extends BaseDataStoreTest
{
    /**
     * @var string
     */
    protected $filename;

    protected $delimiter = ',';

    protected function setUp(): void
    {
        parent::setUp();
        $this->filename = tempnam(sys_get_temp_dir(), 'csv');
        $resource = fopen($this->filename, 'w+');
        fputcsv($resource, $this->getColumns());
        fclose($resource);
    }

    protected function tearDown(): void
    {
        unlink($this->filename);
    }

    protected function createObject(): DataStoreAbstract
    {
        //$lockHandler = new LockHandler($this->filename);
        $lockFactory = new LockFactory(new FlockStore());
        $lockHandler = $lockFactory->createLock($this->filename);

        return new CsvBase($this->filename, $this->delimiter, $lockHandler);
    }
}
