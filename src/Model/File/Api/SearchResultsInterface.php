<?php

namespace App\Model\File\Api;

interface SearchResultsInterface
{
    public function getResults(): array;

    public function getTotalCount(): int;

    public function getCurrentPage(): int;

    public function getTotalPages(): int;

    public function hasNextPage(): bool;

    public function hasPreviousPage(): bool;

    public function nextPage(): void;

    public function previousPage(): void;
}
