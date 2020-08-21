<?php


namespace rollun\datastore\DataStore\Traits;


use rollun\utils\CallAttemptsTrait;

/**
 * Trait MappingFieldsTrait
 * Трейт для датасторов, который умеет мапить данные с массивов и сохранять в базу данных с нужными полями
 * 
 * @todo Add tests
 *
 * @todo Move to repository
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

            if (isset($this->casting) && is_array($this->casting)
                && array_key_exists($field, $this->casting) && $this->fields) {
                $result = $this->cast($this->casting[$field], $result);
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
            $current = $current[$item] ?? null;
        }
        return $current;
    }

    /**
     * Формирует массив для записи в таблицу БД.
     * Ключи и значения массива берутся из поля $fields текущего обьекта
     *
     * @todo
     *
     * @param $itemData
     *
     * @return array
     */
    public function prepareData($itemData)
    {
        $data = [];
        foreach ($this->getFields() as $key => $path) {
            $data[$key] = $this->getValueByFieldName($itemData, $key);
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
     * Преобразовывает данные в нужный тип
     * 
     * @param $type
     * @param $value
     * @return mixed
     */
    protected function cast($type, $value)
    {
        $method = 'cast' . ucfirst($type);
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }

        return $value;
    }

    /**
     * @param $value
     * @return false|string
     */
    protected function castArray($value)
    {
        return json_encode($value);
    }
}