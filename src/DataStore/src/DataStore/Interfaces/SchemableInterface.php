<?php

namespace rollun\datastore\DataStore\Interfaces;

interface SchemableInterface
{
    /**
     * Return scheme for datastore
     *
     * @return mixed
     */
    public function getScheme();
}
