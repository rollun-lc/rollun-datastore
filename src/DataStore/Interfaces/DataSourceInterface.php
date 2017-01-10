<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.07.16
 * Time: 11:44
 */

namespace rolluncom\datastore\DataStore\Interfaces;

interface DataSourceInterface
{

    /**
     * @return array Return data of DataSource
     */
    public function getAll();
}
