<?php

namespace App\Services\Cleanup\Models;

class CleanupConfig
{
    public bool $dryRun = true;
    public bool $createBackup = true;
    public bool $runTests = true;
    public array $includePaths = [];
    public array $excludePaths = [];
    public array $includeFileTypes = ['php', 'js', 'css', 'blade.php'];
    public bool $removeUnusedImports = true;
    public bool $removeUnusedMethods = true;
    public bool $removeUnusedVariables = true;
    public bool $refactorDuplicates = true;
    public bool $createComponents = true;
    public int $batchSize = 50;
    public int $maxFileSize = 1048576; // 1MB in bytes
    
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function isFileTypeIncluded(string $extension): bool
    {
        return in_array($extension, $this->includeFileTypes);
    }
    
    public function isPathExcluded(string $path): bool
    {
        foreach ($this->excludePaths as $excludePath) {
            if (str_contains($path, $excludePath)) {
                return true;
            }
        }
        return false;
    }
}