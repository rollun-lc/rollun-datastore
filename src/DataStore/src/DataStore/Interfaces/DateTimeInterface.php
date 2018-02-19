<?php


namespace rollun\datastore\DataStore\Interfaces;

use rollun\datastore\DataStore\Iterators\DataStoreIterator;

interface DateTimeInterface extends DataStoresInterface
{
    const FIELD_DATETIME = "datetime";
}