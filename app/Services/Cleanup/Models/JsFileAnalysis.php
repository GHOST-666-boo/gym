<?php

namespace App\Services\Cleanup\Models;

class JsFileAnalysis
{
    public string $filePath;
    public array $imports = [];
    public array $functions = [];
    public array $variables = [];
    public array $exports = [];
    public array $dependencies = [];
    public ?object $ast = null;
    
    public function __construct(string $filePath, array $data = [])
    {
        $this->filePath = $filePath;
        
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function hasFunction(string $functionName): bool
    {
        foreach ($this->functions as $function) {
            if (($function['name'] ?? '') === $functionName) {
                return true;
            }
        }
        return false;
    }
    
    public function hasVariable(string $variableName): bool
    {
        foreach ($this->variables as $variable) {
            if (($variable['name'] ?? '') === $variableName) {
                return true;
            }
        }
        return false;
    }
}