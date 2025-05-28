<?php

namespace App\Model\File\Api;

interface InteractiveEditorInterface
{
    public function start(): bool;

    public function action(): string;

    public function search(): void;

    public function browse(): void;

    public function edit(int $rowIndex): void;

    public function save(): bool;
}
