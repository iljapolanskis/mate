<?php

declare(strict_types=1);

namespace App\Command\File;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'file:edit', description: 'Interactive file editor for CSV and other formats')]
class FileEditCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the file to edit')
            ->addOption('search-column', 's', InputOption::VALUE_OPTIONAL, 'Column to use for search', 'sku')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'File format (csv, json, xml)', 'csv');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');
        $searchColumn = $input->getOption('search-column');
        $format = $input->getOption('format');

        // Validate file exists
        if (!file_exists($filePath)) {
            $io->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        // Validate file is readable
        if (!is_readable($filePath)) {
            $io->error("File is not readable: {$filePath}");
            return Command::FAILURE;
        }

        $io->title('File Editor');
        $io->text([
            "File: {$filePath}",
            "Format: {$format}",
            "Search Column: {$searchColumn}",
            "File Size: " . $this->formatBytes(filesize($filePath))
        ]);

        // Handle different formats
        return match (strtolower($format)) {
            'csv' => $this->handleCsvFile($filePath, $searchColumn, $io),
            default => $this->formatNotImplemented($format, $io),
        };
    }

    private function formatNotImplemented(string $format, $io): int
    {
        $format = strtoupper($format);
        $io->warning("{$format} format not implemented yet");
        return Command::FAILURE;
    }

    // TODO: Move it into class later
    private function handleCsvFile(string $filePath, SymfonyStyle $io): int
    {
        // Open file using SplFileObject for memory efficiency
        $file = new \SplFileObject($filePath, 'r');
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);

        // Read first row (headers)
        $file->rewind();
        $headers = $file->current();

        if (!$headers || empty($headers)) {
            $io->error('Unable to read headers from CSV file');
            return Command::FAILURE;
        }

        $table = $io->createTable();
        $table->setHeaders($headers);
        $file->next();
        while ($file->valid()) {
            if (!$row = $file->current()) {
                break;
            }

            $table->addRow($row);
            $file->next();
        }
        $table->render();


        return Command::SUCCESS;
    }

    private function formatBytes(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}
