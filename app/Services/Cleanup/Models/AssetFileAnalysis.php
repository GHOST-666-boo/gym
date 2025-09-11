<?php

namespace App\Services\Cleanup\Models;

class AssetFileAnalysis
{
    public string $filePath;
    public string $type; // 'image', 'font', 'document', etc.
    public int $size;
    public array $referencedBy = [];
    public bool $isUsed = false;
    public ?string $lastModified = null;
    
    public function __construct(string $filePath, array $data = [])
    {
        $this->filePath = $filePath;
        
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function addReference(string $referencingFile): void
    {
        if (!in_array($referencingFile, $this->referencedBy)) {
            $this->referencedBy[] = $referencingFile;
            $this->isUsed = true;
        }
    }
    
    public function getSizeInMB(): float
    {
        return round($this->size / 1048576, 2);
    }
}