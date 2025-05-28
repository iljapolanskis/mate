<?php

namespace App\Model\File\Api;

interface FileHandlerInterface
{
    public function open(string $filePath): bool;

    public function getHeaders(): array;

    public function getTotalRows(): int;

    public function getRow(int $index): array;

    public function updateRow(int $index, array $data): bool;

    public function search(string $column, string $value, string $mode = 'exact'): SearchResultsInterface;

    public function save(): bool;

    public function hasUnsavedChanges(): bool;

    public function close(): void;
}
