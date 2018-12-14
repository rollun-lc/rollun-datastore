<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\DataSource;

class MemoryConfig implements DataSourceInterface
{
    /**
     * @var array
     */
    protected $items;

    /**
     * MemoryConfig constructor.
     * @param array $items
     */
    public function __construct($items = [])
    {
        foreach ($items as $key => $item) {
            if (isset($item['id'])) {
                $this->items[$item['id']] = $item;
            } else {
                $this->items[$key] = $item;
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getAll()
    {
        return $this->items;
    }
}
