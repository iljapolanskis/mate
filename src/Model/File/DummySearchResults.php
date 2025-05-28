<?php
declare(strict_types=1);

namespace App\Model\File;

use App\Model\File\Api\SearchResultsInterface;

class DummySearchResults implements SearchResultsInterface
{
    private int $currentPage = 1;
    private int $totalPages = 1;

    public function __construct(
        private array $results = [],
        private int $pageSize = 10,
    ) {
        $this->totalPages = (int)ceil(count($results) / $pageSize);
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getTotalCount(): int
    {
        return count($this->results);
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function nextPage(): void
    {
        if ($this->hasNextPage()) {
            $this->currentPage++;
        }
    }

    public function previousPage(): void
    {
        if ($this->hasPreviousPage()) {
            $this->currentPage--;
        }
    }
}
