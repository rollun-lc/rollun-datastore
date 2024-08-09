<?php

namespace rollun\datastore\DataStore;

use Laminas\Db\Adapter\Exception\InvalidQueryException;
use Laminas\Db\Adapter\Exception\RuntimeException;
use Throwable;

class LaminasDbExceptionDetector
{
    private const MYSQL_SERVER_HAS_GONE_AWAY = 'MySQL server has gone away';

    public static function isConnectionException(Throwable $e): bool
    {
        // When connection is lost during preparing SQL in Laminas\Db\Adapter\Driver\Mysqli\Statement::prepare()
        if ($e instanceof InvalidQueryException && $e->getPrevious()) {
            if (str_starts_with($e->getPrevious()->getMessage(), self::MYSQL_SERVER_HAS_GONE_AWAY)) {
                return true;
            }
        }

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

    public static function isOperationTimedOutException(Throwable $e): bool
    {
        if (!$e instanceof RuntimeException) {
            return false;
        }
        if (
            // Exception message from a Mysqli driver: Laminas\Db\Adapter\Driver\Mysqli\Statement
            str_starts_with($e->getMessage(), self::MYSQL_SERVER_HAS_GONE_AWAY)
        ) {
            return true;
        }
        return false;
    }
}