<?php


namespace rollun\datastore\DataStore\Traits;


/**
 * Trait MappingFieldsTrait
 * Трейт для датасторов, который умеет мапить данные с массивов и сохранять в базу данных с нужными полями
 *
 * @todo Move to repository or utils
 * 
 * @package rollun\datastore\DataStore\Traits
 */
trait MappingFieldsTrait
{
    /**
     * Возвращает путь в массиве для конкретного поля.
     * Данные берутся из поля $fields соответствующего класса.
     *
     * @param $field
     *
     * @return mixed|null
     */
    public function getFieldPath($field) {
        if (array_key_exists($field, $this->getFields())) {
            return $this->getFields()[$field];
        }

        return null;
    }

    /**
     * Получает значение из массива по переданному названию поля.
     * Сначала определяется путь в массиве для указанного поля. Потом по пути достается значение
     * Если нужно отформатировать значение, можно определить метод format{$field}Field, например, formatOrderIdField
     *
     * @param $itemData
     * @param $field
     *
     * @return |null
     */
    public function getValueByFieldName($itemData, $field) {
        if ($path = $this->getFieldPath($field)) {
            $result = $this->getValueByFieldPath($itemData, $path);
            $formatMethod = 'format' . str_replace('_', '', ucwords($field, '_')) . 'Field';
            if (method_exists($this, $formatMethod)) {
                $result = $this->$formatMethod($result);
            }

            if ($this->needCast($field)) {
                $result = $this->cast($this->getCasting()[$field], $result);
            }
        }

        return $result ?? null;
    }

    /**
     * Возращает значение из массива по указанному пути.
     *
     * @param $itemData
     * @param $path
     *
     * @return |null
     *@see AbstractMappingTableDataStore::getFieldPath
     *
     */
    protected function getValueByFieldPath($itemData, $path) {
        $paths = explode('.', $path);
        $current = $itemData;
        foreach ($paths as $item) {
            if (is_object($current)) {
                $current = $current->{$item} ?? null;
            } else {
                $current = $current[$item] ?? null;
            }
        }
        return $current;
    }

    /**
     * Формирует массив для записи в таблицу БД.
     * Ключи и значения массива берутся из поля $fields текущего обьекта
     *
     * @param $itemData
     *
     * @param null $callback
     *
     * @return array
     *
     * @todo
     *
     */
    public function prepareData($itemData, $callback = null)
    {
        $data = [];
        foreach ($this->getFields() as $key => $path) {
            $value = $this->getValueByFieldName($itemData, $key);
            // TODO
            if (is_callable($callback)) {
                $value = $callback($value, $key, $itemData);
            }
            $data[$key] = $value;
        }
        return $data;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields ?? [];
    }

    /**
     * @param $fields
     */
    public function setFields($fields)
    {
        if (property_exists($this, 'fields')) {
            $this->fields = $fields;
        }
    }

    /**
     * @param $key
     *
     * @param $value
     */
    public function addField($key, $value)
    {
        if (property_exists($this, 'fields')) {
            $this->fields[$key] = $value;
        }
    }

    /**
     * @return array
     */
    public function getCasting()
    {
        return $this->casting ?? [];
    }

    /**
     * @param $casting
     */
    public function setCasting($casting)
    {
        if (property_exists($this, 'casting')) {
            $this->casting = $casting;
        }
    }

    /**
     * @param $key
     * @param $value
     */
    public function addCasting($key, $value)
    {
        if (property_exists($this, 'casting')) {
            $this->casting[$key] = $value;
        }
    }

    /**
     * @param $field
     * @return bool
     *
     * @todo
     */
    protected function needCast($field)
    {
        return isset($this->casting)
            && is_array($this->casting)
            && array_key_exists($field, $this->casting)
            && $this->getFields();
    }

    /**
     * Преобразовывает данные в нужный тип
     * 
     * @param $type
     * @param $value
     * @return mixed
     */
    protected function cast($type, $value)
    {
        $method = 'cast' . str_replace('_', '', ucwords($type, '_'));
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }

        return $value;
    }

    /**
     * @param $value
     * @return false|string
     */
    protected function castJson($value)
    {
        return json_encode($value);
    }

    /**
     * @param $value
     * @return array
     */
    protected function castArray($value)
    {
        return (array) $value;
    }

    /**
     * @param $value
     * @return int
     */
    protected function castInteger($value)
    {
        return (int) $value;
    }

    /**
     * @param $value
     * @return int
     */
    protected function castInt($value)
    {
        return $this->castInteger($value);
    }

    /**
     * @param $value
     * @return float
     */
    protected function castFloat($value)
    {
        return (float) $value;
    }

    /**
     * @param $value
     * @return float
     */
    protected function castDouble($value)
    {
        return $this->castFloat($value);
    }

    /**
     * @param $value
     * @return string
     */
    protected function castString($value)
    {
        return (string) $value;
    }
}