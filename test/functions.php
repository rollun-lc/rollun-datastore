<?php

namespace rollun\test;

function runScriptInBackground(string $scriptPath, string $args = ''): int {
    $pid = shell_exec("php \"$scriptPath\"  $args > /dev/null 2>&1 & echo $!");
    return (int) trim($pid);
}

function isProcessRunning(int $pid): bool
{
    // Выполняем команду kill с сигналом 0 для проверки существования процесса
    $result = shell_exec("kill -0 $pid 2>&1");

    // Если команда завершилась без ошибок, значит процесс существует
    return empty($result);
}

function killProcess(int $pid): void
{
    shell_exec("kill -9 $pid");
}
