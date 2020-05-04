<?php

namespace rollun\test\unit\Files;

use rollun\files\FileObject;
use rollun\files\FileManager;
use rollun\installer\Command;
use PHPUnit\Framework\TestCase;

abstract class FilesAbstractTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function tearDown()
    {
        $fullFilename = $this->makeFullFileName();
        @unlink($fullFilename);
    }

    protected function makeDirName()
    {
        $fileManager = new FileManager;
        $dataDir = Command::getDataDir();
        $pathArray = explode('\\', strtolower(get_class($this)));
        array_shift($pathArray);
        $subDir = implode('/', $pathArray);
        $dirName = $fileManager->joinPath($dataDir, $subDir);
        return $dirName;
    }

    protected function makeFileName()
    {
        $name = pathinfo($name = get_class($this) . '.txt')['basename'];
        return $name;
    }

    protected function makeFullFileName($filename = null)
    {
        $fileManager = new FileManager;
        $dirName = $this->makeDirName();
        $fileManager->createDir($dirName);
        $filename = $filename ?? $this->makeFileName();
        $fullFilename = $fileManager->joinPath($dirName, $filename);
        return $fullFilename;
    }

    protected function makeFile(string $stringInFile)
    {
        $fileManager = new FileManager;
        $fullFilename = $this->makeFullFileName();
        $stream = $fileManager->createAndOpenFile($fullFilename, true);
        $fileManager->closeStream($stream);
        file_put_contents($fullFilename, $stringInFile);

        return $fullFilename;
    }
}
