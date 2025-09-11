<?php

namespace App\Services\Cleanup\Models;

class ReferenceUpdate
{
    public string $filePath;
    public string $oldReference;
    public string $newReference;
    public int $lineNumber;
    public string $context;
    
    public function __construct(
        string $filePath,
        string $oldReference,
        string $newReference,
        int $lineNumber = 0,
        string $context = ''
    ) {
        $this->filePath = $filePath;
        $this->oldReference = $oldReference;
        $this->newReference = $newReference;
        $this->lineNumber = $lineNumber;
        $this->context = $context;
    }
    
    public function getSearchPattern(): string
    {
        return preg_quote($this->oldReference, '/');
    }
    
    public function getReplacement(): string
    {
        return $this->newReference;
    }
}