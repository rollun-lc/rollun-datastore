<?php


namespace rollun\datastore\DataStore\Traits;

use rollun\utils\CallAttemptsTrait;


trait PrepareFieldsTrait
{
    use MappingFieldsTrait;
    use CallAttemptsTrait;

    /**
     * Добавляет запись в базу данных.
     * Пытается сделать это определенное количество раз, если с первого раза не получается
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function createData($itemData)
    {
        $itemData = $this->prepareData($itemData);
        return $this->callAttempts(function() use ($itemData) {
            return $this->create($itemData);
        });
    }

    /**
     * Алиас для createData
     * @see MappingFieldsTrait::createData()
     *
     * @param $itemData
     * @return array
     *
     * @deprecated
     *
     * @throws \Throwable
     */
    public function insertData($itemData)
    {
        return $this->createData($itemData);
    }

    /**
     * Обновляет запись в базе данных.
     * Пытается сделать это определенное количество раз, если с первого раза не получается
     *
     * @param $itemData
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function updateData($itemData)
    {
        $itemData = $this->prepareData($itemData);
        return $this->callAttempts(function() use ($itemData){
            return $this->update($itemData);
        });
    }

    /**
     * @param $record
     *
     * @return array
     *
     * @throws \Exception
     */
    public function rewriteData($record)
    {
        $itemData = $this->prepareData($record);
        return $this->callAttempts(function() use ($itemData){
            return $this->rewrite($itemData);
        });
        //return $this->callAttemptsMethod('rewrite', $itemData);
    }
}