<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18.01.17
 * Time: 15:48
 */

namespace rollun\datastore\Viewer;

interface ViewerInterface
{
    /**
     * Return Widget with
     * @return string
     */
    public function getWidget();

    /**
     * @return string
     */
    public function getPage();
}
