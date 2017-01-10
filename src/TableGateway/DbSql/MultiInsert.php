<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 21.07.16
 * Time: 13:36
 */

namespace rolluncom\datastore\TableGateway\DbSql;

use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\Exception\InvalidArgumentException;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Select;

class MultiInsert extends Insert
{
    protected $specifications = [
        self::SPECIFICATION_INSERT => 'INSERT INTO %1$s (%2$s) VALUES %3$s',
        self::SPECIFICATION_SELECT => 'INSERT INTO %1$s %2$s %3$s',
    ];

    public function values($values, $flag = self::VALUES_SET)
    {
        if ($values instanceof Select) {
            if ($flag == self::VALUES_MERGE) {
                throw new InvalidArgumentException(
                    'A Zend\Db\Sql\Select instance cannot be provided with the merge flag'
                );
            }
            $this->select = $values;
            return $this;
        }

        if (!is_array($values)) {
            throw new InvalidArgumentException(
                'values() expects an array of values or Zend\Db\Sql\Select instance'
            );
        }

        if ($this->select && $flag == self::VALUES_MERGE) {
            throw new InvalidArgumentException(
                'An array of values cannot be provided with the merge flag when a Zend\Db\Sql\Select instance already exists as the value source'
            );
        }

        if ($flag == self::VALUES_SET) {
            if ($this->isArrayOfArray($values)) {
                foreach ($values as $key => $value) {
                    if (!$this->isAssocativeArray($value)) {
                        $value = array_combine(array_keys($this->columns), array_values($value));
                    }
                    foreach ($value as $column => $item) {
                        $this->columns[$column][$key] = $item;
                    }
                }
            } else {
                foreach ($values as $column => $item) {
                    $this->columns[$column][] = $item;
                }
            }
        } else {
            if ($this->isArrayOfArray($values)) {
                //rework, get $key in $item
                foreach ($values as $key => $item) {
                    foreach ($item as $column => $value) {
                        $this->columns[$column][$key] = $value;
                    }
                }
            } else {
                foreach ($values as $column => $value) {
                    $this->columns[$column][0] = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Simple test for an array of array
     *
     * @param array $array
     * @return bool
     */
    private function isArrayOfArray(array $array)
    {
        return isset($array[0]) && is_array($array[0]);
    }

    /**
     * Simple test for an associative array
     *
     * @link http://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
     * @param array $array
     * @return bool
     */
    private function isAssocativeArray(array $array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    protected function processInsert(PlatformInterface $platform, DriverInterface $driver = null, ParameterContainer $parameterContainer = null)
    {
        if ($this->select) {
            return;
        }
        if (!$this->columns) {
            throw new InvalidArgumentException('values or select should be present');
        }

        $columns = [];
        $values = [];

        foreach ($this->columns as $column => $value) {

            $columns[] = $platform->quoteIdentifier($column);
            foreach ($value as $key => $item) {
                /* if (is_scalar($item) && $parameterContainer) {
                     $values[$key][] = $driver->formatParameterName($column);
                     $parameterContainer->offsetSet($column, $item);
                 } else {*/
                $values[$key][] = $this->resolveColumnValue(
                    $item,
                    $platform,
                    $driver,
                    $parameterContainer
                );
                /* }*/
            }
        }

        $strValues = '';
        foreach ($values as $value) {
            $strValues .= '(' . implode(', ', $value) . '),';
        }
        $strValues = rtrim($strValues, ',');

        $sql =  sprintf(
            $this->specifications[static::SPECIFICATION_INSERT],
            $this->resolveTable($this->table, $platform, $driver, $parameterContainer),
            implode(', ', $columns),
            $strValues
        );

        return $sql;
    }

}
