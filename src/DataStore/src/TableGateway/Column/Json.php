<?php

declare(strict_types=1);

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\TableGateway\Column;

use Zend\Db\Sql\Ddl\Column\Column;

/**
 * JSON column type for MySQL
 *
 * Usage:
 * <code>
 * $column = new Json('field_name', true, null, ['comment' => 'JSON data']);
 * </code>
 */
class Json extends Column
{
    /** @var string */
    protected $type = 'JSON';

    /**
     * @param string|null $name
     * @param bool $nullable
     * @param mixed|null $default
     * @param array $options
     */
    public function __construct($name = null, $nullable = false, $default = null, array $options = [])
    {
        parent::__construct($name, $nullable, $default, $options);
    }
}
