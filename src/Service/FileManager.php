<?php
declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;

class FileManager
{
    public function __construct(
        private Filesystem $filesystem,
    ) {

    }

    public function saveTextToFile(string $content, $filePathAbsolute): bool
    {
        try {
            $this->filesystem->dumpFile($filePathAbsolute, $content);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    public function getFileContents($filePathAbsolute): string
    {
        if (!file_exists($filePathAbsolute)) {
            return '';
        }

        try {
            return file_get_contents($filePathAbsolute);
        } catch (\Throwable) {
            return '';
        }
    }
}
