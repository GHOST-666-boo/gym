<?php

namespace App\Services\Cleanup\Models;

class RefactoringResult
{
    public bool $success;
    public array $refactoringsApplied = [];
    public array $errors = [];
    public array $filesCreated = [];
    public array $filesModified = [];
    public array $backupPaths = [];
    public int $duplicatesRemoved = 0;
    public int $linesReduced = 0;
    public float $executionTime = 0.0;
    
    public function __construct(
        bool $success,
        array $refactoringsApplied = [],
        array $errors = []
    ) {
        $this->success = $success;
        $this->refactoringsApplied = $refactoringsApplied;
        $this->errors = $errors;
    }
    
    public function addError(string $error): void
    {
        $this->errors[] = $error;
        $this->success = false;
    }
    
    public function addRefactoring(string $type, string $description, array $details = []): void
    {
        $this->refactoringsApplied[] = [
            'type' => $type,
            'description' => $description,
            'details' => $details,
            'timestamp' => now()
        ];
    }
    
    public function addFileCreated(string $filePath): void
    {
        $this->filesCreated[] = $filePath;
    }
    
    public function addFileModified(string $filePath): void
    {
        $this->filesModified[] = $filePath;
    }
    
    public function addBackup(string $originalPath, string $backupPath): void
    {
        $this->backupPaths[$originalPath] = $backupPath;
    }
    
    public function getRefactoringCount(): int
    {
        return count($this->refactoringsApplied);
    }
    
    public function getTotalFilesAffected(): int
    {
        return count(array_unique(array_merge($this->filesCreated, $this->filesModified)));
    }
}