<?php

namespace Tests\Unit\Services\Cleanup;

use App\Services\Cleanup\JavaScriptAnalyzer;
use App\Services\Cleanup\Models\JsFileAnalysis;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class JavaScriptAnalyzerTest extends TestCase
{
    private JavaScriptAnalyzer $analyzer;
    private string $testFilesPath;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new JavaScriptAnalyzer();
        $this->testFilesPath = base_path('tests/fixtures/js');
        
        // Create test directory if it doesn't exist
        if (!File::exists($this->testFilesPath)) {
            File::makeDirectory($this->testFilesPath, 0755, true);
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->testFilesPath)) {
            File::deleteDirectory($this->testFilesPath);
        }
        
        parent::tearDown();
    }
    
    public function test_parse_file_with_imports_and_functions()
    {
        $jsContent = <<<'JS'
import React from 'react';
import { useState, useEffect } from 'react';
import './styles.css';

const MyComponent = () => {
    const [count, setCount] = useState(0);
    
    useEffect(() => {
        console.log('Component mounted');
    }, []);
    
    return <div>{count}</div>;
};

function helperFunction(param1, param2) {
    return param1 + param2;
}

export default MyComponent;
export { helperFunction };
JS;
        
        $testFile = $this->testFilesPath . '/test-component.js';
        File::put($testFile, $jsContent);
        
        $analysis = $this->analyzer->parseFile($testFile);
        
        $this->assertInstanceOf(JsFileAnalysis::class, $analysis);
        $this->assertEquals($testFile, $analysis->filePath);
        $this->assertNotEmpty($analysis->imports);
        $this->assertNotEmpty($analysis->functions);
        $this->assertNotEmpty($analysis->variables);
        $this->assertNotEmpty($analysis->exports);
        
        // Check imports
        $importNames = array_column($analysis->imports, 'name');
        $this->assertContains('React', $importNames);
        $this->assertContains('useState', $importNames);
        $this->assertContains('useEffect', $importNames);
        
        // Check functions
        $functionNames = array_column($analysis->functions, 'name');
        $this->assertContains('MyComponent', $functionNames);
        $this->assertContains('helperFunction', $functionNames);
        
        // Check variables
        $variableNames = array_column($analysis->variables, 'name');
        $this->assertContains('count', $variableNames);
        $this->assertContains('setCount', $variableNames);
        
        // Check exports
        $exportNames = array_column($analysis->exports, 'name');
        $this->assertContains('MyComponent', $exportNames);
        $this->assertContains('helperFunction', $exportNames);
    }
    
    public function test_parse_file_with_class_and_methods()
    {
        $jsContent = <<<'JS'
import EventEmitter from 'events';

class ImageGallery extends EventEmitter {
    constructor() {
        super();
        this.currentIndex = 0;
        this.images = [];
    }
    
    init() {
        this.bindEvents();
    }
    
    bindEvents() {
        // Event binding logic
    }
    
    openModal(src, alt = '') {
        // Modal opening logic
    }
}

export default ImageGallery;
JS;
        
        $testFile = $this->testFilesPath . '/image-gallery.js';
        File::put($testFile, $jsContent);
        
        $analysis = $this->analyzer->parseFile($testFile);
        
        $this->assertInstanceOf(JsFileAnalysis::class, $analysis);
        
        // Check imports
        $importNames = array_column($analysis->imports, 'name');
        $this->assertContains('EventEmitter', $importNames);
        
        // Check functions (class methods are treated as functions)
        $functionNames = array_column($analysis->functions, 'name');
        $this->assertContains('constructor', $functionNames);
        $this->assertContains('init', $functionNames);
        $this->assertContains('bindEvents', $functionNames);
        $this->assertContains('openModal', $functionNames);
        
        // Check exports
        $exportNames = array_column($analysis->exports, 'name');
        $this->assertContains('ImageGallery', $exportNames);
    }
    
    public function test_find_unused_imports()
    {
        $jsContent = <<<'JS'
import React from 'react';
import { useState, useEffect } from 'react';
import lodash from 'lodash';
import './unused-styles.css';

const MyComponent = () => {
    const [count, setCount] = useState(0);
    
    // useEffect and lodash are not used
    return <div>{count}</div>;
};

export default MyComponent;
JS;
        
        $testFile = $this->testFilesPath . '/unused-imports.js';
        File::put($testFile, $jsContent);
        
        $analysis = $this->analyzer->parseFile($testFile);
        $unusedImports = $this->analyzer->findUnusedImports($analysis);
        
        $unusedImportNames = array_column($unusedImports, 'name');
        $this->assertContains('useEffect', $unusedImportNames);
        $this->assertContains('lodash', $unusedImportNames);
        $this->assertNotContains('React', $unusedImportNames);
        $this->assertNotContains('useState', $unusedImportNames);
    }
    
    public function test_find_unused_variables()
    {
        $jsContent = <<<'JS'
const usedVariable = 'I am used';
const unusedVariable = 'I am not used';
let anotherUnused = 42;

function myFunction() {
    console.log(usedVariable);
    return 'result';
}

export { myFunction };
JS;
        
        $testFile = $this->testFilesPath . '/unused-variables.js';
        File::put($testFile, $jsContent);
        
        $analysis = $this->analyzer->parseFile($testFile);
        $unusedVariables = $this->analyzer->findUnusedVariables($analysis);
        
        $unusedVariableNames = array_column($unusedVariables, 'name');
        $this->assertContains('unusedVariable', $unusedVariableNames);
        $this->assertContains('anotherUnused', $unusedVariableNames);
        $this->assertNotContains('usedVariable', $unusedVariableNames);
    }
    
    public function test_find_duplicate_functions()
    {
        $jsContent1 = <<<'JS'
function duplicateFunction(param1, param2) {
    return param1 + param2;
}

function uniqueFunction() {
    return 'unique';
}
JS;
        
        $jsContent2 = <<<'JS'
function duplicateFunction(param1, param2) {
    return param1 * param2; // Different implementation but same signature
}

function anotherUnique() {
    return 'another';
}
JS;
        
        $testFile1 = $this->testFilesPath . '/file1.js';
        $testFile2 = $this->testFilesPath . '/file2.js';
        
        File::put($testFile1, $jsContent1);
        File::put($testFile2, $jsContent2);
        
        $analysis1 = $this->analyzer->parseFile($testFile1);
        $analysis2 = $this->analyzer->parseFile($testFile2);
        
        $duplicates = $this->analyzer->findDuplicateFunctions([$analysis1, $analysis2]);
        
        $this->assertNotEmpty($duplicates);
        
        $duplicateSignatures = array_column($duplicates, 'signature');
        $this->assertContains('duplicateFunction(param1,param2)', $duplicateSignatures);
        
        // Check that each duplicate has multiple occurrences
        foreach ($duplicates as $duplicate) {
            $this->assertGreaterThan(1, count($duplicate['occurrences']));
        }
    }
    
    public function test_parse_nonexistent_file_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('JavaScript file not found');
        
        $this->analyzer->parseFile('/nonexistent/file.js');
    }
    
    public function test_parse_file_with_syntax_errors_returns_empty_analysis()
    {
        $jsContent = <<<'JS'
// This is invalid JavaScript syntax
function invalidFunction( {
    return 'missing closing parenthesis';
}
JS;
        
        $testFile = $this->testFilesPath . '/invalid-syntax.js';
        File::put($testFile, $jsContent);
        
        $analysis = $this->analyzer->parseFile($testFile);
        
        $this->assertInstanceOf(JsFileAnalysis::class, $analysis);
        $this->assertEquals($testFile, $analysis->filePath);
        // Should return empty arrays for invalid syntax
        $this->assertEmpty($analysis->imports);
        $this->assertEmpty($analysis->functions);
        $this->assertEmpty($analysis->variables);
        $this->assertEmpty($analysis->exports);
    }
    
    public function test_analysis_model_helper_methods()
    {
        $analysis = new JsFileAnalysis('/test/file.js', [
            'functions' => [
                ['name' => 'testFunction'],
                ['name' => 'anotherFunction']
            ],
            'variables' => [
                ['name' => 'testVariable'],
                ['name' => 'anotherVariable']
            ]
        ]);
        
        $this->assertTrue($analysis->hasFunction('testFunction'));
        $this->assertFalse($analysis->hasFunction('nonexistentFunction'));
        
        $this->assertTrue($analysis->hasVariable('testVariable'));
        $this->assertFalse($analysis->hasVariable('nonexistentVariable'));
    }
}