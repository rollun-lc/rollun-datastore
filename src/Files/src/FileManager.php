<?php
declare(strict_types=1);

namespace rollun\files;

use Exception;
use RuntimeException;

/**
 * Class FileManager
 *
 * @author  Andrey Zaboychenko
 * @author  Roman Ratsun <r.ratsun.rollun@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class FileManager
{
    /**
     * Tries timeout (in ms)
     */
    const LOCK_TRIES_TIMEOUT = 50;

    /**
     * Maximum of locking tries
     */
    const MAX_LOCK_TRIES = 40;

    /**
     *
     * Method for join the path
     *
     * @param mixed ...$arguments
     *
     * @return string C:/dir/file.csv
     * @example joinPath(('C:/', '/dir', ' \file.csv') -> 'C:/dir/file.csv'
     */
    public function joinPath(...$arguments): string
    {
        $paths = [];
        foreach ($arguments as $arg) {
            if (trim($arg, ' ') !== '') {
                $paths[] = trim($arg, ' ');
            }
        }

        return str_replace('p:/', 'p://', preg_replace('#/+#', '/', str_replace('\\', '/', join('/', $paths))));
    }

    /**
     * @param string $dirname
     *
     * @throws RuntimeException
     */
    public function createDir(string $dirname): void
    {
        if (!(file_exists($dirname) && is_dir($dirname))) {
            try {
                $result = mkdir($dirname, 0777, true);
            } catch (Exception $exc) {
                throw new RuntimeException($exc->getMessage() . PHP_EOL . ' Dir name: ' . $dirname);
            }
            if (!$result) {
                throw new RuntimeException('Wrong dir name: ' . $dirname);
            }
        }
    }

    /**
     *
     * IF $dirname is file - it will be delete
     *
     * @param string $dirname
     *
     * @return bool
     * @throws RuntimeException
     */
    public function deleteDirRecursively(string $dirname): bool
    {
        if (!realpath($dirname)) {
            throw new RuntimeException('Wrong dir name: ' . $dirname);
        }

        if (!file_exists($dirname)) {
            return true;
        }

        if (!is_dir($dirname)) {
            try {
                return $this->deleteFile($dirname);
            } catch (Exception $exc) {
                throw new RuntimeException($exc->getMessage() . ' Filename: ' . $dirname);
            }
        }

        foreach (scandir($dirname) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->deleteDirRecursively($dirname . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dirname);
    }

    /**
     * @param string $fullFilename
     * @param string $mode
     *
     * @return false|resource
     * @throws RuntimeException
     */
    public function openFile(string $fullFilename, string $mode = 'r')
    {
        $count = 0;
        while (!$stream = fopen($fullFilename, $mode)) {
            if ($count++ > static::MAX_LOCK_TRIES) {
                throw new RuntimeException('Can not open the file: ' . $fullFilename);
            }
            usleep(static::LOCK_TRIES_TIMEOUT);
        }

        return $stream;
    }

    /**
     * You have to unlock and close $stream after using
     * Use for create only:
     * $this->closeStream($this->createAndOpenFile($fullFilename));
     *
     * Use for open only:
     * $this->openFile($fullFilename,'w+');
     *
     * @param string $fullFilename
     * @param bool   $rewriteIfExist
     *
     * @return false|resource
     * @throws RuntimeException
     */
    public function createAndOpenFile(string $fullFilename, bool $rewriteIfExist = false)
    {
        $dirname = dirname($fullFilename);
        $this->createDir($dirname);

        if (file_exists($fullFilename) && is_file($fullFilename)) {
            if ($rewriteIfExist) {
                $stream = $this->openFile($fullFilename, 'c+');
                $this->lockEx($stream, $fullFilename);
                ftruncate($stream, 0);
                return $stream;
            } else {
                throw new RuntimeException('File ' . $fullFilename . ' already exists');
            }
        } else {
            $stream = fopen($fullFilename, 'w+');
            $this->lockEx($stream, $fullFilename);
        }

        return $stream;
    }

    /**
     * @param resource $stream
     *
     * @return void
     */
    public function closeStream($stream): void
    {
        flock($stream, LOCK_UN);
        fclose($stream);
    }

    /**
     * @param string $fullFilename
     *
     * @return bool
     * @throws RuntimeException
     */
    public function deleteFile(string $fullFilename): bool
    {
        if (!realpath($fullFilename)) {
            throw new RuntimeException('Wrong file name: ' . $fullFilename);
        }

        if (file_exists($fullFilename) && is_file($fullFilename)) {
            $stream = $this->openFile($fullFilename, 'c+');
            $this->lockEx($stream);
            $this->closeStream($stream);
            unlink($fullFilename);
        }

        return true;
    }

    /**
     * @param resource $stream
     * @param string   $fullFilename
     *
     * @return void
     * @throws RuntimeException
     */
    protected function lockEx($stream, $fullFilename = ''): void
    {
        $count = 0;
        while (!flock($stream, LOCK_EX | LOCK_NB, $wouldblock)) {
            if (!$wouldblock) {
                throw new RuntimeException('There is a problem with file: ' . $fullFilename);
            }
            if ($count++ > static::MAX_LOCK_TRIES) {
                throw new RuntimeException('Can not lock the file: ' . $fullFilename);
            }
            usleep(static::LOCK_TRIES_TIMEOUT);
        }
    }
}
