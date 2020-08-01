<?php

namespace rollun\repository\Interfaces;


interface ModelInterface
{
    public function toArray();

    public function getAttribute($name);
}