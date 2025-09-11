<?php

namespace App\Services\Cleanup\Models;

class CodebaseAnalysis
{
    public array $phpFiles = [];           // PhpFileAnalysis[]
    public array $jsFiles = [];            // JsFileAnalysis[]
    public array $bladeFiles = [];         // BladeTemplateAnalysis[]
    public array $cssFiles = [];           // CssFileAnalysis[]
    public array $routeDefinitions = [];   // RouteAnalysis[]
    public array $assetFiles = [];         // AssetFileAnalysis[]
    public ?DependencyGraph $dependencies = null;
    
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function getTotalFiles(): int
    {
        return count($this->phpFiles) + count($this->jsFiles) + 
               count($this->bladeFiles) + count($this->cssFiles);
    }
}