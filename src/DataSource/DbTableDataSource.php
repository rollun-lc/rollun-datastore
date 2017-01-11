<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.07.16
 * Time: 12:51
 */
namespace rollun\datastore\DataSource;

use Xiag\Rql\Parser\Query;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\Interfaces\DataSourceInterface;

class DbTableDataSource extends DbTable implements DataSourceInterface
{

    /**
     * @return array Return data of DataSource
     */
    public function getAll()
    {
        return $this->query(new Query());
    }
}
