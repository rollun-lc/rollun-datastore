<?php
declare(strict_types=1);

namespace rollun\files;

use Ajgl\Csv\Rfc\Spl\SplFileObject as Base;
use Ajgl\Csv\Rfc\CsvRfcUtils;
use InvalidArgumentException;
use RuntimeException;

/**
 * The FileObject class offers an object oriented interface for a file.
 *
 * @author  Andrey Zaboychenko
 * @author  Roman Ratsun <r.ratsun.rollun@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class FileObject extends Base
{
    /**
     * Tries timeout (in ms)
     */
    const DEFAULT_LOCK_TRIES_TIMEOUT = 50;

    /**
     * Maximum of locking tries
     */
    const DEFAULT_MAX_LOCK_TRIES = 20 * 10;

    /**
     * Buffer size in  bytes for coping operation
     */
    const DEFAULT_MAX_BUFFER_SIZE = 10000000;

    /**
     * @var int
     */
    public $lockTriesTimeout = self::DEFAULT_LOCK_TRIES_TIMEOUT;

    /**
     * @var int
     */
    public $maxLockTries = self::DEFAULT_MAX_LOCK_TRIES;

    /**
     * @var int
     */
    protected $maxBufferSize = self::DEFAULT_MAX_BUFFER_SIZE;

    /**
     * FileObject constructor.
     *
     * @param string $filename
     * @param string $open_mode
     * @param bool   $use_include_path
     * @param null   $context
     */
    public function __construct($filename, $open_mode = 'c+', $use_include_path = false, $context = null)
    {
        parent::__construct($filename, $open_mode, $use_include_path, $context);

        // \SplFileObject::DROP_NEW_LINE | \SplFileObject::READ_AHEAD |\SplFileObject::SKIP_EMPTY | \SplFileObject::READ_CSV
        $this->setFlags(0);
    }

    /**
     * FileObject destructor.
     */
    public function __destruct()
    {
        $this->unlock();
    }

    /**
     * @param int $maxBufferSize
     *
     * @return void
     */
    public function setMaxBufferSize(int $maxBufferSize): void
    {
        $this->maxBufferSize = $maxBufferSize;
    }

    /**
     * @return int
     */
    public function getMaxBufferSize(): int
    {
        return $this->maxBufferSize;
    }

    /**
     * @param int $lockMode | LOCK_SH or LOCK_EX
     * @param int $maxLockTries
     * @param int $lockTriesTimeout
     *
     * @return bool
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function lock(int $lockMode, int $maxLockTries = null, int $lockTriesTimeout = null): bool
    {
        $maxTries = $maxLockTries ?? $this->maxLockTries;
        $triesTimeout = $lockTriesTimeout ?? $this->lockTriesTimeout;

        if ($lockMode <> LOCK_SH && $lockMode <> LOCK_EX) {
            throw new InvalidArgumentException('$lockMode must be LOCK_SH or LOCK_EX');
        }

        $count = 0;
        while (!$this->flock($lockMode | LOCK_NB, $wouldblock)) {
            if (!$wouldblock) {
                throw new RuntimeException('There is a problem with file: ' . $this->getRealPath());
            }
            if ($count++ > $maxTries) {
                throw new RuntimeException('Can not lock the file: ' . $this->getRealPath());
            }
            usleep($triesTimeout);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function unlock(): bool
    {
        return $this->flock(LOCK_UN);
    }

    /**
     * @return int
     */
    public function getStringsCount(): int
    {
        if ($this->getFileSize() === 0) {
            return 0;
        }

        $flags = $this->clearFlags();
        $this->seek(PHP_INT_MAX);
        $key = $this->key();
        $this->fseekWithCheck(-1, SEEK_END);
        $lastChar = $this->fread(1);
        $shift = $lastChar === "\n" ? 0 : 1;
        $stringsCount = $key + $shift;
        $this->restoreFlags($flags);

        return $stringsCount;
    }

    /**
     * @param int $linePos
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function deleteString(int $linePos): void
    {
        $maxLinePos = $this->getStringsCount() - 1;
        if ($linePos > $maxLinePos) {
            throw new InvalidArgumentException("Can not delete  \$linePos = $linePos . Max linePos is $maxLinePos in file: \n" . $this->getRealPath());
        }
        $flags = $this->clearFlags();
        if ($linePos === 0) {
            $this->rewind();
            $newCharPos = 0;
        } else {
            $this->seek($linePos - 1);
            $this->current();
            $newCharPos = $this->ftell();
            $this->next();
        }
        $this->current();
        $charPosFrom = $this->ftell();
        $this->moveBackward($charPosFrom, $newCharPos);
        $this->restoreFlags($flags);
    }

    /**
     * @param int $offset
     * @param int $whence
     *
     * @return int
     * @throws RuntimeException
     */
    public function fseekWithCheck(int $offset, int $whence = SEEK_SET): int
    {
        if ($this->fseek($offset, $whence) == -1) {
            throw new RuntimeException("Can not fseek to $offset =  . $offset \n in file: \n" . $this->getRealPath());
        }

        return 0;
    }

    /**
     * @param string   $string
     * @param int|null $length
     *
     * @return int
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function fwriteWithCheck(string $string, int $length = null): int
    {
        $lengthForWrite = is_null($length) ? strlen($string) : $length;
        if ($lengthForWrite > strlen($string)) {
            throw new InvalidArgumentException("\$length = $length bigger then = strlen('$string') in file: \n" . $this->getRealPath());
        }
        $writedLength = $this->fwrite($string, $lengthForWrite);
        if ($writedLength !== $lengthForWrite) {
            throw new RuntimeException("Error writing \$string = $string in file: \n" . $this->getRealPath());
        }

        return $writedLength;
    }

    /**
     * @return int|null
     */
    public function getFileSize(): ?int
    {
        $position = $this->ftell();
        $this->fseekWithCheck(0, SEEK_END);
        $fileSize = $this->ftell();
        $this->fseekWithCheck($position);

        return ($fileSize === false) ? null : $fileSize;
    }

    /**
     * @param string $insertedString string for insert. \n will be added if not exist.
     * @param int    $beforeLinePos  zero based line number. null for uppend to the end of file.
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function insertString(string $insertedString, int $beforeLinePos = null): void
    {
        $insertedString = rtrim($insertedString, "\r\n") . "\n";

        if ($this->getStringsCount() === 0 && (is_null($beforeLinePos) || $beforeLinePos === 0)) {
            $this->fseekWithCheck(0, SEEK_END);
            $this->fwriteWithCheck($insertedString);

            return;
        }

        if (is_null($beforeLinePos)) {
            $this->fseekWithCheck(-1, SEEK_END);
            $lastChar = $this->fread(1);
            $prefix = $lastChar === "\n" ? '' : "\n";
            $this->fwriteWithCheck($prefix . $insertedString);

            return;
        }

        $flags = $this->clearFlags();
        if ($beforeLinePos === 0) {
            $charPosFrom = 0;
            $this->seek($beforeLinePos);
        } else {
            $this->seek($beforeLinePos - 1);
            $this->current();
            $charPosFrom = $this->ftell();
        }
        if ($this->eof()) {
            throw new InvalidArgumentException("\$beforeLinePos = $beforeLinePos bigger then max index\n in file: \n" . $this->getRealPath());
        }
        $newCharPos = $charPosFrom + strlen($insertedString);
        $this->moveForward($charPosFrom, $newCharPos);
        $this->fseekWithCheck($charPosFrom);
        $this->fwriteWithCheck($insertedString);
        $this->restoreFlags($flags);
    }

    /**
     * @param string $newString
     * @param int    $inLinePos
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function rewriteString(string $newString, int $inLinePos): void
    {
        $maxLinePos = $this->getStringsCount() - 1;
        if ($inLinePos > $maxLinePos) {
            throw new InvalidArgumentException("Can not rewrite \$inLinePos = {$inLinePos}. Max linePos is $maxLinePos in file: \n" . $this->getRealPath());
        }
        $flags = $this->clearFlags();
        $newString = rtrim($newString, "\r\n") . "\n";
        if ($inLinePos === 0) {
            $charPosStart = 0;
            $this->rewind();
        } else {
            $this->seek($inLinePos - 1);
            $this->current();
            $charPosStart = $this->ftell();
            $this->next();
        }
        $this->current();

        $charPosFrom = $this->ftell();
        $charPosTo = $charPosStart + strlen($newString);
        $this->moveSubStr($charPosFrom, $charPosTo);
        $this->fseekWithCheck($charPosStart);
        $this->fwriteWithCheck($newString);
        $this->restoreFlags($flags);
    }

    /**
     * @param int    $newFileSize
     * @param string $placeholderChar
     *
     * @return int|null
     */
    public function truncateWithCheck(int $newFileSize, string $placeholderChar = ' '): ?int
    {
        $flags = $this->clearFlags();
        $changes = $this->changeFileSize($newFileSize, $placeholderChar);
        $this->restoreFlags($flags);

        return $changes;
    }

    /**
     * Move last part of file (from $charPosFrom to EOF) to $newCharPos
     *
     * @param int $charPosFrom
     * @param int $newCharPos
     *
     * @return void
     */
    public function moveSubStr(int $charPosFrom, int $newCharPos): void
    {
        if ($charPosFrom === $newCharPos) {
            return;
        }
        $flags = $this->clearFlags();
        if ($charPosFrom < $newCharPos) {
            $this->moveForward($charPosFrom, $newCharPos);
        } else {
            $this->moveBackward($charPosFrom, $newCharPos);
        }
        $this->restoreFlags($flags);
    }

    /**
     * @inheritDoc
     */
    public function fputcsv($fields, $delimiter = ',', $enclosure = '"', $escape = '\\')
    {
        CsvRfcUtils::checkPutCsvEscape($escape);
        $result = $this->fwrite(CsvRfcUtils::strPutCsv($fields, $delimiter, $enclosure));

        return $result === 0 ? null : $result;
    }

    /**
     * @return int
     */
    protected function clearFlags(): int
    {
        $flagsForRestore = $this->getFlags();
        $this->setFlags($flagsForRestore & \SplFileObject::READ_CSV);

        return $flagsForRestore;
    }

    /**
     * @param int $flagsForRestore
     *
     * @return void
     */
    protected function restoreFlags(int $flagsForRestore): void
    {
        $this->setFlags($flagsForRestore);
    }

    /**
     * @param int $charPosFrom
     * @param int $newCharPos
     *
     * @return void
     */
    protected function moveForward(int $charPosFrom, int $newCharPos): void
    {
        $fileSize = $this->getFileSize();
        $this->changeFileSize($fileSize + $newCharPos - $charPosFrom);
        $bufferSize = ($charPosFrom + $this->getMaxBufferSize()) > $fileSize ? $fileSize - $charPosFrom : $this->getMaxBufferSize();
        $charPosForRead = $fileSize - $bufferSize;
        $charPosForWrite = $fileSize + $newCharPos - $charPosFrom - $bufferSize;
        while ($bufferSize > 0) {
            $this->fseekWithCheck($charPosForRead);
            $buffer = $this->fread($bufferSize);
            $this->fseekWithCheck($charPosForWrite);
            $this->fwriteWithCheck($buffer);
            $bufferSize = ($charPosFrom + $this->getMaxBufferSize()) > $charPosForRead ? $charPosForRead - $charPosFrom : $this->getMaxBufferSize();
            $charPosForRead = $charPosForRead - $bufferSize;
            $charPosForWrite = $charPosForWrite - $bufferSize;
        }
        $this->fflush();
    }

    /**
     * @param int $charPosFrom
     * @param int $newCharPos
     *
     * @return void
     */
    protected function moveBackward(int $charPosFrom, int $newCharPos): void
    {
        $fileSize = $this->getFileSize();
        $this->fseekWithCheck($charPosFrom);
        while ($charPosFrom < $fileSize) {
            $this->fseekWithCheck($charPosFrom);
            $bufferSize = ($charPosFrom + $this->getMaxBufferSize()) > $fileSize ? $fileSize - $charPosFrom : $this->getMaxBufferSize();
            $buffer = $this->fread($bufferSize);
            $charPosFrom = $this->ftell();
            $this->fseekWithCheck($newCharPos);
            $this->fwriteWithCheck($buffer);
            $newCharPos = $this->ftell();
        }
        $this->fflush();
        $this->changeFileSize($newCharPos);
    }

    /**
     * @param int    $newFileSize
     * @param string $placeholderChar if $newFileSize > $this->fileeSithe()
     * @param int    $oldFileSize     - do not set this fild!
     *
     * @return int
     * @throws RuntimeException
     */
    protected function changeFileSize(int $newFileSize, string $placeholderChar = ' ', int $oldFileSize = null): int
    {
        $fileSize = $this->getFileSize();
        if ($newFileSize === $fileSize) {
            return 0;
        }

        if ($newFileSize < $fileSize) {
            $success = $this->ftruncate($newFileSize);
            if (!$success) {
                throw new RuntimeException("Error changeFileSize to $newFileSize bytes \n in file: \n" . $this->getRealPath());
            }
            return $newFileSize - $fileSize;
        }

        $oldFileSize = $oldFileSize ?? $fileSize;
        $addQuantity = $this->getMaxBufferSize() < ($newFileSize - $fileSize) ? $this->getMaxBufferSize() : $newFileSize - $fileSize;
        $string = str_repeat($placeholderChar, $addQuantity);
        $this->fseekWithCheck(0, SEEK_END);
        $this->fwriteWithCheck($string);
        $currentFileSize = $this->getFileSize();
        if ($currentFileSize == $fileSize) {
            throw new RuntimeException("Error changeFileSize to $newFileSize bytes \n in file: \n" . $this->getRealPath());
        }
        if ($currentFileSize == $newFileSize) {
            return $newFileSize - $oldFileSize;
        } else {
            return $this->changeFileSize($newFileSize, $placeholderChar);
        }
    }
}
