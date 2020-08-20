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
    public function createData($itemData, $rewriteIfExist = false)
    {
        $itemData = $this->prepareData($itemData);
        return $this->callAttempts(function() use ($itemData, $rewriteIfExist) {
            return $this->create($itemData, $rewriteIfExist);
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
     * @param bool $createIfAbsent
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function updateData($itemData, $createIfAbsent = false)
    {
        $itemData = $this->prepareData($itemData);
        return $this->callAttempts(function() use ($itemData, $createIfAbsent){
            return $this->update($itemData, $createIfAbsent);
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