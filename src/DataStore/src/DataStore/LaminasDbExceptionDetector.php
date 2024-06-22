<?php

namespace rollun\datastore\DataStore;

use Laminas\Db\Adapter\Exception\RuntimeException;
use Throwable;

class LaminasDbExceptionDetector
{
    public static function isConnectionException(Throwable $e): bool
    {
        if (!$e instanceof RuntimeException) {
            return false;
        }
        if (
            // Exception message from a PDO driver: Laminas\Db\Adapter\Driver\Pdo\Connection
            str_starts_with($e->getMessage(), 'Connect Error:') ||
            // Exception message from a Mysqli driver: Laminas\Db\Adapter\Driver\Mysqli\Connection
            str_starts_with($e->getMessage(), 'Connection error')
        ) {
            return true;
        }
        return false;
    }
}