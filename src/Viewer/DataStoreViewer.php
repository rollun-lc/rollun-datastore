<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18.01.17
 * Time: 18:46
 */

namespace rollun\datastore\Viewer;

class DataStoreViewerInterface implements ViewerInterface
{
    /**
     * @var string
     */
    protected $resourceName;

    public function __construct($resourceName)
    {
        $this->resourceName = $resourceName;
    }

    /**
     * Return Widget with
     * @return string
     */
    public function getWidget()
    {
        // TODO: Implement getWidget() method.
    }

    /**
     * @return string
     */
    public function getPage()
    {
        // TODO: Implement getPage() method.
    }
}
