<?php

namespace App\Services\Cleanup\Models;

class BladeTemplateAnalysis
{
    public string $filePath;
    public array $components = [];
    public array $variables = [];
    public array $includes = [];
    public array $sections = [];
    public array $htmlStructures = [];
    public string $content = '';
    
    public function __construct(string $filePath, array $data = [])
    {
        $this->filePath = $filePath;
        
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function hasComponent(string $componentName): bool
    {
        return in_array($componentName, $this->components);
    }
    
    public function hasVariable(string $variableName): bool
    {
        return in_array($variableName, $this->variables);
    }
}