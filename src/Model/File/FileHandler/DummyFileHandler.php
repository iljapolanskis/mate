<?php
declare(strict_types=1);

namespace App\Model\File\FileHandler;

use App\Model\File\Api\FileHandlerInterface;
use App\Model\File\Api\SearchResultsInterface;
use App\Model\File\DummySearchResults;

class DummyFileHandler implements FileHandlerInterface
{
    private array $headers = ['sku', 'name', 'price', 'category', 'stock'];
    private array $dummyData = [];
    private bool $isOpen = false;
    private bool $hasChanges = false;
    private string $filePath = '';

    public function __construct()
    {
        // Generate some dummy data
        for ($i = 1; $i <= 100; $i++) {
            $this->dummyData[] = [
                'sku' => "SKU-{$i}",
                'name' => "Product {$i}",
                'price' => rand(10, 1000) / 10,
                'category' => ['Electronics', 'Clothing', 'Books', 'Home'][rand(0, 3)],
                'stock' => rand(0, 100)
            ];
        }
    }

    public function open(string $filePath): bool
    {
        echo "📁 Opening file: {$filePath}\n";
        $this->filePath = $filePath;
        $this->isOpen = true;
        return true;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getTotalRows(): int
    {
        return count($this->dummyData);
    }

    public function search(string $column, string $value, string $mode = 'exact'): SearchResultsInterface
    {
        echo "🔍 Searching in column '{$column}' for '{$value}' (mode: {$mode})\n";

        $results = [];
        foreach ($this->dummyData as $index => $row) {
            if (!isset($row[$column])) {
                continue;
            }

            $cellValue = (string)$row[$column];
            $match = match ($mode) {
                'exact' => $cellValue === $value,
                'contains' => str_contains(strtolower($cellValue), strtolower($value)),
                'starts_with' => str_starts_with(strtolower($cellValue), strtolower($value)),
                default => false
            };

            if ($match) {
                $results[] = ['index' => $index, 'data' => $row];
            }
        }

        return new DummySearchResults($results);
    }

    public function getRow(int $index): array
    {
        echo "📖 Getting row {$index}\n";
        return $this->dummyData[$index] ?? [];
    }

    public function updateRow(int $index, array $data): bool
    {
        echo "✏️ Updating row {$index}\n";
        if (isset($this->dummyData[$index])) {
            $this->dummyData[$index] = array_merge($this->dummyData[$index], $data);
            $this->hasChanges = true;
            return true;
        }
        return false;
    }

    public function save(): bool
    {
        echo "💾 Saving changes to {$this->filePath}\n";
        $this->hasChanges = false;
        return true;
    }

    public function hasUnsavedChanges(): bool
    {
        return $this->hasChanges;
    }

    public function close(): void
    {
        echo "🔒 Closing file: {$this->filePath}\n";
        $this->isOpen = false;
    }
}
