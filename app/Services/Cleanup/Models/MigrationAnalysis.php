<?php

namespace App\Services\Cleanup\Models;

class MigrationAnalysis
{
    public string $fileName;
    public string $filePath;
    public string $className;
    public string $tableName;
    public string $operation; // create, alter, drop, etc.
    public array $columns = [];
    public bool $isUsed = true; // Migrations are generally considered used unless proven otherwise
    public bool $hasCorrespondingModel = false;
    public ?string $timestamp = null;
    
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        // Extract timestamp from filename
        if ($this->fileName && preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_/', $this->fileName, $matches)) {
            $this->timestamp = $matches[1];
        }
    }
    
    public function addColumn(string $name, string $type, array $attributes = []): void
    {
        $this->columns[] = [
            'name' => $name,
            'type' => $type,
            'attributes' => $attributes
        ];
    }
    
    public function isCreateTable(): bool
    {
        return $this->operation === 'create';
    }
    
    public function isAlterTable(): bool
    {
        return $this->operation === 'alter';
    }
    
    public function isDropTable(): bool
    {
        return $this->operation === 'drop';
    }
    
    public function getAge(): int
    {
        if (!$this->timestamp) {
            return 0;
        }
        
        $migrationDate = \DateTime::createFromFormat('Y_m_d_His', $this->timestamp);
        $now = new \DateTime();
        
        return $now->diff($migrationDate)->days;
    }
}