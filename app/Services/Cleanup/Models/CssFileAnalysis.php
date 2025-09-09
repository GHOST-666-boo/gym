<?php

namespace App\Services\Cleanup\Models;

class CssFileAnalysis
{
    public string $filePath;
    public array $classes = [];
    public array $ids = [];
    public array $rules = [];
    public array $imports = [];
    public array $mediaQueries = [];
    
    public function __construct(string $filePath, array $data = [])
    {
        $this->filePath = $filePath;
        
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function hasClass(string $className): bool
    {
        foreach ($this->classes as $class) {
            if (($class['name'] ?? '') === $className) {
                return true;
            }
        }
        return false;
    }
    
    public function hasId(string $idName): bool
    {
        foreach ($this->ids as $id) {
            if (($id['name'] ?? '') === $idName) {
                return true;
            }
        }
        return false;
    }
}