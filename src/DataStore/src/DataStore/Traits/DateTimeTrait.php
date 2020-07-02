<?php


namespace rollun\datastore\DataStore\Traits;


use rollun\datastore\DataStore\Interfaces\DateTimeInterface;

/**
 * Trait DateTimeTrait
 * @package rollun\datastore\DataStore\Traits
 */
trait DateTimeTrait
{
    /**
     * @var string
     */
    protected $dateTimeFormat = 'Y-m-d H:i:s';

    /**
     * @param $itemData
     * @param bool $rewriteIfExist
     * @return mixed
     * @throws \Exception
     */
    public function insertItem($itemData, $rewriteIfExist = false)
    {
        if (empty($itemData[DateTimeInterface::FIELD_CREATED_AT])) {
            $date = new \DateTime('now', new \DateTimeZone('UTC'));
            $itemData[DateTimeInterface::FIELD_CREATED_AT] = $date->format($this->dateTimeFormat);
        }

        return parent::insertItem($itemData, $rewriteIfExist);
    }

    /**
     * @param $itemData
     * @param bool $createIfAbsent
     * @return mixed
     * @throws \Exception
     */
    public function updateItem($itemData, $createIfAbsent = false)
    {
        if (empty($itemData[DateTimeInterface::FIELD_UPDATED_AT])) {
            $date = new \DateTime('now', new \DateTimeZone('UTC'));
            $itemData[DateTimeInterface::FIELD_UPDATED_AT] = $date->format($this->dateTimeFormat);
        }

        return parent::updateItem($itemData, $createIfAbsent);
    }
}