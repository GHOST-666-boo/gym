<?php

namespace App\Services\Cleanup\Models;

class FileModificationResult
{
    public bool $success;
    public string $filePath;
    public array $modificationsApplied = [];
    public array $errors = [];
    public ?string $backupPath = null;
    public int $linesRemoved = 0;
    public int $bytesReduced = 0;
    public float $executionTime = 0.0;
    
    public function __construct(
        bool $success,
        string $filePath,
        array $modificationsApplied = [],
        array $errors = []
    ) {
        $this->success = $success;
        $this->filePath = $filePath;
        $this->modificationsApplied = $modificationsApplied;
        $this->errors = $errors;
    }
    
    public function addError(string $error): void
    {
        $this->errors[] = $error;
        $this->success = false;
    }
    
    public function addModification(string $type, string $description): void
    {
        $this->modificationsApplied[] = [
            'type' => $type,
            'description' => $description,
            'timestamp' => now()
        ];
    }
    
    public function getModificationCount(): int
    {
        return count($this->modificationsApplied);
    }
}