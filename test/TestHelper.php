<?php

namespace rollun\test;

/**
 * Адаптер для замены rollun-installer в тестах
 * Используется когда rollun-installer не совместим с текущей версией Composer/PHP
 */
class TestHelper
{
    /**
     * Замена для Command::getDataDir() из rollun-installer
     * Возвращает путь к директории data в корне проекта
     * 
     * @return string
     */
    public static function getDataDir()
    {
        return realpath('./') . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
    }
}
