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
        // Раніше mysqli при помилках, по замовчуванню, повертав варнінг. Цей варнінг оброблювався
        // Laminas драйвером для Mysqli і перетворювався в Laminas\Db\Adapter\Exception\RuntimeException.
        // В php8.1 змінили поведінку mysqli по замовчуванню і замість варнінгів відразу кидаються ексепшени.
        // (MySQLi's default error mode is changed from MYSQLI_REPORT_OFF to MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT)
        // Ці ексепшени не ловляться Laminas драйвером і долітають сюди в чистому вигляді. Тому потрібно
        // їх також перевіряти.
        // @see https://php.watch/versions/8.1/mysqli-error-mode
        if (!$e instanceof RuntimeException && !$e instanceof \mysqli_sql_exception) {
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