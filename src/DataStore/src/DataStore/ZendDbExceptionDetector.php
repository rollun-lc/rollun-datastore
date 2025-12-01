<?php

namespace rollun\datastore\DataStore;

use Zend\Db\Adapter\Exception\InvalidQueryException;
use Zend\Db\Adapter\Exception\RuntimeException;
use Throwable;

class ZendDbExceptionDetector
{
    private const MYSQL_SERVER_HAS_GONE_AWAY = 'MySQL server has gone away';

    public static function isConnectionException(Throwable $e): bool
    {
        // When connection is lost during preparing SQL in Zend\Db\Adapter\Driver\Mysqli\Statement::prepare()
        if ($e instanceof InvalidQueryException && $e->getPrevious()) {
            if (self::startsWith($e->getPrevious()->getMessage(), self::MYSQL_SERVER_HAS_GONE_AWAY)) {
                return true;
            }
        }

        if (!$e instanceof RuntimeException) {
            return false;
        }
        if (
            // Exception message from a PDO driver: Zend\Db\Adapter\Driver\Pdo\Connection
            self::startsWith($e->getMessage(), 'Connect Error:') ||
            // Exception message from a Mysqli driver: Zend\Db\Adapter\Driver\Mysqli\Connection
            self::startsWith($e->getMessage(), 'Connection error')
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
            // Exception message from a Mysqli driver: Zend\Db\Adapter\Driver\Mysqli\Statement
            self::startsWith($e->getMessage(), self::MYSQL_SERVER_HAS_GONE_AWAY)
        ) {
            return true;
        }
        return false;
    }

    /**
     * PHP 7.2 compatible version of str_starts_with (available in PHP 8.0+)
     */
    private static function startsWith(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) === 0;
    }
}
