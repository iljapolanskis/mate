<?php

namespace App\Model\File\Editor;

use App\Model\File\Api\FileHandlerInterface;
use App\Model\File\Api\InteractiveEditorInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InteractiveFileEditor implements InteractiveEditorInterface
{
    public function __construct(
        private FileHandlerInterface $fileHandler,
        private ?SymfonyStyle $io,
    ) {
    }

    public function __destruct()
    {
        $this->fileHandler->close();
    }

    public function start(): bool
    {
        while (true) {
            $choice = $this->action();

            match ($choice) {
                'search' => $this->search(),
                'browse' => $this->browse(),
                'save' => $this->save(),
                'exit' => $this->handleExit(),
                default => $this->io->error('Invalid choice, please try again.')
            };

            if ($choice === 'exit') {
                break;
            }
        }

        return true;
    }

    public function action(): string
    {
        $this->io->section('📋 Main Menu');

        $choices = [
            'search' => '🔍 Search Records',
            'browse' => '📖 Browse Records',
            'save' => '💾 Save Changes',
            'exit' => '🚪 Exit'
        ];

        return $this->io->choice('What would you like to do?', $choices);
    }

    public function search(): void
    {
        $this->io->section('🔍 Search Records');

        $headers = $this->fileHandler->getHeaders();
        $searchColumn = $this->io->choice('Select column to search in:', $headers);

        $searchModes = [
            'exact' => 'Exact match',
            'contains' => 'Contains text',
            'starts_with' => 'Starts with'
        ];

        $searchMode = $this->io->choice('Search mode:', $searchModes);
        $searchValue = $this->io->ask('Enter search value:');

        if (empty($searchValue)) {
            $this->io->warning('Search value cannot be empty');
            return;
        }

        $this->io->text('Searching...');
        $this->currentSearchResults = $this->fileHandler->search($searchColumn, $searchValue, $searchMode);

        $this->displaySearchResults();
    }

    private function displaySearchResults(): void
    {
        if (!$this->currentSearchResults) {
            $this->io->warning('No search results to display');
            return;
        }

        $results = $this->currentSearchResults->getResults();
        $totalCount = $this->currentSearchResults->getTotalCount();

        if ($totalCount === 0) {
            $this->io->warning('No results found');
            return;
        }

        $this->io->success("Found {$totalCount} result(s)");

        // Display results in a table
        $table = $this->io->createTable();
        $table->setHeaders(['#', ...array_keys($results[0]['data'])]);

        foreach ($results as $result) {
            $row = [$result['index']];
            foreach ($result['data'] as $value) {
                $row[] = $this->truncateString((string)$value, 20);
            }
            $table->addRow($row);
        }

        $table->render();

        // Ask user what to do with results
        $this->handleSearchResults($results);
    }

    private function handleSearchResults(array $results): void
    {
        $choices = [
            'edit' => '✏️ Edit a record',
            'back' => '⬅️ Back to main menu'
        ];

        $action = $this->io->choice('What would you like to do?', $choices);

        if ($action === 'edit') {
            $rowNumber = $this->io->ask('Enter row number to edit:', null, function ($value) use ($results) {
                if (!is_numeric($value)) {
                    throw new \InvalidArgumentException('Row number must be numeric');
                }

                $rowIndex = (int)$value;
                $validIndices = array_column($results, 'index');

                if (!in_array($rowIndex, $validIndices)) {
                    throw new \InvalidArgumentException('Invalid row number');
                }

                return $rowIndex;
            });

            $this->edit($rowNumber);
        }
    }

    public function browse(): void
    {
        $this->io->section('📖 Browse Records');

        $totalRows = $this->fileHandler->getTotalRows();
        $startRow = $this->io->ask("Enter starting row number (1-{$totalRows}):", '1', function ($value) use ($totalRows) {
            $num = (int)$value;
            if ($num < 1 || $num > $totalRows) {
                throw new \InvalidArgumentException("Row number must be between 1 and {$totalRows}");
            }
            return $num - 1; // Convert to 0-based index
        });

        $this->displayRowsFromIndex($startRow);
    }

    private function displayRowsFromIndex(int $startIndex): void
    {
        $pageSize = 10;
        $totalRows = $this->fileHandler->getTotalRows();

        $table = $this->io->createTable();
        $table->setHeaders(['#', ...$this->fileHandler->getHeaders()]);

        for ($i = $startIndex; $i < min($startIndex + $pageSize, $totalRows); $i++) {
            $row = $this->fileHandler->getRow($i);
            $tableRow = [$i];

            foreach ($row as $value) {
                $tableRow[] = $this->truncateString((string)$value, 20);
            }

            $table->addRow($tableRow);
        }

        $table->render();

        $choices = [
            'edit' => '✏️ Edit a record',
            'next' => '➡️ Next page',
            'prev' => '⬅️ Previous page',
            'back' => '🔙 Back to main menu'
        ];

        // Remove navigation options if not applicable
        if ($startIndex + $pageSize >= $totalRows) {
            unset($choices['next']);
        }
        if ($startIndex === 0) {
            unset($choices['prev']);
        }

        $action = $this->io->choice('What would you like to do?', $choices);

        match ($action) {
            'edit' => $this->handleEditFromBrowse($startIndex, $pageSize),
            'next' => $this->displayRowsFromIndex($startIndex + $pageSize),
            'prev' => $this->displayRowsFromIndex(max(0, $startIndex - $pageSize)),
            'back' => null
        };
    }

    private function handleEditFromBrowse(int $startIndex, int $pageSize): void
    {
        $totalRows = $this->fileHandler->getTotalRows();
        $maxRow = min($startIndex + $pageSize, $totalRows) - 1;

        $rowNumber = $this->io->ask("Enter row number to edit ({$startIndex}-{$maxRow}):", null, function ($value) use ($startIndex, $maxRow) {
            if (!is_numeric($value)) {
                throw new \InvalidArgumentException('Row number must be numeric');
            }

            $rowIndex = (int)$value;
            if ($rowIndex < $startIndex || $rowIndex > $maxRow) {
                throw new \InvalidArgumentException("Row number must be between {$startIndex} and {$maxRow}");
            }

            return $rowIndex;
        });

        $this->edit($rowNumber);
    }

    public function edit(int $rowIndex): void
    {
        $this->io->section("✏️ Editing Row #{$rowIndex}");

        $currentData = $this->fileHandler->getRow($rowIndex);
        if (empty($currentData)) {
            $this->io->error('Row not found');
            return;
        }

        $this->io->text('Current values:');
        $this->io->definitionList(...$currentData);

        $updatedData = [];

        foreach ($currentData as $field => $currentValue) {
            $newValue = $this->io->ask(
                "Enter new value for '{$field}' (current: {$currentValue}):",
                $currentValue
            );

            if ($newValue !== $currentValue) {
                $updatedData[$field] = $newValue;
            }
        }

        if (empty($updatedData)) {
            $this->io->info('No changes made');
            return;
        }

        $this->io->text('Changes to be made:');
        $this->io->definitionList(...$updatedData);

        if ($this->io->confirm('Save these changes?', false)) {
            if ($this->fileHandler->updateRow($rowIndex, $updatedData)) {
                $this->io->success('Row updated successfully!');
            } else {
                $this->io->error('Failed to update row');
            }
        }
    }

    public function save(): bool
    {
        if (!$this->fileHandler->hasUnsavedChanges()) {
            $this->io->info('No changes to save');
            return true;
        }

        $this->io->section('💾 Save Changes');

        if ($this->io->confirm('Are you sure you want to save all changes?', false)) {
            if ($this->fileHandler->save()) {
                $this->io->success('Changes saved successfully!');
                return true;
            } else {
                $this->io->error('Failed to save changes');
                return false;
            }
        }

        return false;
    }

    private function handleExit(): void
    {
        if ($this->fileHandler->hasUnsavedChanges()) {
            $this->io->warning('You have unsaved changes!');

            $choices = [
                'save' => '💾 Save and exit',
                'discard' => '🗑️ Discard changes and exit',
                'cancel' => '❌ Cancel (don\'t exit)'
            ];

            $choice = $this->io->choice('What would you like to do?', $choices);

            match ($choice) {
                'save' => $this->save(),
                'discard' => $this->io->caution('Changes discarded'),
                'cancel' => null
            };
        }

        $this->io->success('Goodbye!👋');
    }

    private function truncateString(string $text, int $length): string
    {
        return strlen($text) > $length ? substr($text, 0, $length - 3) . '...' : $text;
    }
}
