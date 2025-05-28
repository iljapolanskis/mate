<?php
declare(strict_types=1);

namespace App\Service\Magento;

use Symfony\Component\Finder\Finder;

class FileScanner
{
    public function findCrontabFiles(string $magentoRootAbsolute): array
    {
        $appCode = $this->findByPattern('crontab.xml', $magentoRootAbsolute . '/app/code/*/*/etc/');
        $vendor = $this->findByPattern('crontab.xml', $magentoRootAbsolute . '/vendor/*/*/etc/');
        return array_merge($vendor, $appCode);
    }

    public function findByPattern(string $pattern, string $absolutePath): array
    {
        $finder = new Finder();
        $finder->files()
            ->in($absolutePath)
            ->name($pattern)
            ->ignoreDotFiles(true)
            ->ignoreVCS(true);

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $files;
    }
}
