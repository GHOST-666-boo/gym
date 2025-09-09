<?php

namespace Tests\Unit\Services\Cleanup;

use App\Services\Cleanup\CssAnalyzer;
use App\Services\Cleanup\Models\CssFileAnalysis;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CssAnalyzerTest extends TestCase
{
    private CssAnalyzer $analyzer;
    private string $testFilesPath;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new CssAnalyzer();
        $this->testFilesPath = base_path('tests/fixtures/css');
        
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
    
    public function test_parse_file_with_classes_and_ids()
    {
        $cssContent = <<<'CSS'
/* Main styles */
.header {
    background-color: #fff;
    padding: 20px;
}

#navigation {
    display: flex;
    justify-content: space-between;
}

.btn-primary {
    background: blue;
    color: white;
}

.card-hover:hover {
    transform: scale(1.05);
}

#footer {
    margin-top: auto;
}
CSS;
        
        $testFile = $this->testFilesPath . '/test-styles.css';
        File::put($testFile, $cssContent);
        
        $analysis = $this->analyzer->parseFile($testFile);
        
        $this->assertInstanceOf(CssFileAnalysis::class, $analysis);
        $this->assertEquals($testFile, $analysis->filePath);
        $this->assertNotEmpty($analysis->classes);
        $this->assertNotEmpty($analysis->ids);
        $this->assertNotEmpty($analysis->rules);
        
        // Check classes
        $classNames = array_column($analysis->classes, 'name');
        $this->assertContains('header', $classNames);
        $this->assertContains('btn-primary', $classNames);
        $this->assertContains('card-hover', $classNames);
        
        // Check IDs
        $idNames = array_column($analysis->ids, 'name');
        $this->assertContains('navigation', $idNames);
        $this->assertContains('footer', $idNames);
        
        // Check rules
        $this->assertGreaterThan(0, count($analysis->rules));
    }
    
    public function test_parse_file_with_media_queries_and_imports()
    {
        $cssContent = <<<'CSS'
@import './base.css';
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

.container {
    max-width: 1200px;
    margin: 0 auto;
}

@media (max-width: 768px) {
    .container {
        padding: 0 16px;
    }
    
    .mobile-hidden {
        display: none;
    }
}

@media print {
    .no-print {
        display: none !important;
    }
}
CSS;
        
        $testFile = $this->testFilesPath . '/responsive-styles.css';
        File::put($testFile, $cssContent);
        
        $analysis = $this->analyzer->parseFile($testFile);
        
        $this->assertInstanceOf(CssFileAnalysis::class, $analysis);
        
        // Check imports
        $this->assertNotEmpty($analysis->imports);
        $importPaths = array_column($analysis->imports, 'path');
        $this->assertContains('./base.css', $importPaths);
        $this->assertContains('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap', $importPaths);
        
        // Check media queries
        $this->assertNotEmpty($analysis->mediaQueries);
        $mediaConditions = array_column($analysis->mediaQueries, 'condition');
        $this->assertContains('(max-width: 768px)', $mediaConditions);
        $this->assertContains('print', $mediaConditions);
        
        // Check classes
        $classNames = array_column($analysis->classes, 'name');
        $this->assertContains('container', $classNames);
        $this->assertContains('mobile-hidden', $classNames);
        $this->assertContains('no-print', $classNames);
    }
    
    public function test_find_unused_classes()
    {
        $cssContent = <<<'CSS'
.used-class {
    color: red;
}

.unused-class {
    color: blue;
}

.another-unused {
    font-size: 16px;
}
CSS;
        
        $htmlContent = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Test</title>
</head>
<body>
    <div class="used-class">This class is used</div>
    <p>Some content without the unused classes</p>
</body>
</html>
HTML;
        
        $cssFile = $this->testFilesPath . '/unused-classes.css';
        $htmlFile = $this->testFilesPath . '/test.html';
        
        File::put($cssFile, $cssContent);
        File::put($htmlFile, $htmlContent);
        
        $analysis = $this->analyzer->parseFile($cssFile);
        $unusedClasses = $this->analyzer->findUnusedClasses($analysis, [$htmlFile]);
        
        $unusedClassNames = array_column($unusedClasses, 'name');
        $this->assertContains('unused-class', $unusedClassNames);
        $this->assertContains('another-unused', $unusedClassNames);
        $this->assertNotContains('used-class', $unusedClassNames);
    }
    
    public function test_find_unused_ids()
    {
        $cssContent = <<<'CSS'
#used-id {
    background: yellow;
}

#unused-id {
    background: green;
}

#another-unused-id {
    margin: 10px;
}
CSS;
        
        $htmlContent = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Test</title>
</head>
<body>
    <div id="used-id">This ID is used</div>
    <a href="#used-id">Link to used ID</a>
    <p>Some content without the unused IDs</p>
</body>
</html>
HTML;
        
        $cssFile = $this->testFilesPath . '/unused-ids.css';
        $htmlFile = $this->testFilesPath . '/test-ids.html';
        
        File::put($cssFile, $cssContent);
        File::put($htmlFile, $htmlContent);
        
        $analysis = $this->analyzer->parseFile($cssFile);
        $unusedIds = $this->analyzer->findUnusedIds($analysis, [$htmlFile]);
        
        $unusedIdNames = array_column($unusedIds, 'name');
        $this->assertContains('unused-id', $unusedIdNames);
        $this->assertContains('another-unused-id', $unusedIdNames);
        $this->assertNotContains('used-id', $unusedIdNames);
    }
    
    public function test_find_duplicate_rules()
    {
        $cssContent1 = <<<'CSS'
.button {
    background: blue;
    color: white;
    padding: 10px;
}

.unique-class {
    margin: 5px;
}
CSS;
        
        $cssContent2 = <<<'CSS'
.btn {
    background: blue;
    color: white;
    padding: 10px;
}

.another-unique {
    border: 1px solid black;
}
CSS;
        
        $cssFile1 = $this->testFilesPath . '/file1.css';
        $cssFile2 = $this->testFilesPath . '/file2.css';
        
        File::put($cssFile1, $cssContent1);
        File::put($cssFile2, $cssContent2);
        
        $analysis1 = $this->analyzer->parseFile($cssFile1);
        $analysis2 = $this->analyzer->parseFile($cssFile2);
        
        $duplicates = $this->analyzer->findDuplicateRules([$analysis1, $analysis2]);
        
        $this->assertNotEmpty($duplicates);
        
        // Check that each duplicate has multiple occurrences
        foreach ($duplicates as $duplicate) {
            $this->assertGreaterThan(1, count($duplicate['occurrences']));
        }
    }
    
    public function test_parse_nonexistent_file_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CSS file not found');
        
        $this->analyzer->parseFile('/nonexistent/file.css');
    }
    
    public function test_parse_file_with_complex_selectors()
    {
        $cssContent = <<<'CSS'
/* Complex selectors */
.nav > li:first-child {
    margin-left: 0;
}

#header .logo img {
    max-height: 50px;
}

.card:hover .card-title {
    color: blue;
}

@media (min-width: 768px) {
    .responsive-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Pseudo-elements and pseudo-classes */
.button::before {
    content: '';
    position: absolute;
}

.input:focus {
    outline: 2px solid blue;
}

/* Attribute selectors */
input[type="text"] {
    border: 1px solid #ccc;
}

.icon[data-type="warning"] {
    color: orange;
}
CSS;
        
        $testFile = $this->testFilesPath . '/complex-selectors.css';
        File::put($testFile, $cssContent);
        
        $analysis = $this->analyzer->parseFile($testFile);
        
        $this->assertInstanceOf(CssFileAnalysis::class, $analysis);
        
        // Check that complex selectors are parsed
        $classNames = array_column($analysis->classes, 'name');
        $this->assertContains('nav', $classNames);
        $this->assertContains('logo', $classNames);
        $this->assertContains('card', $classNames);
        $this->assertContains('card-title', $classNames);
        $this->assertContains('responsive-grid', $classNames);
        $this->assertContains('button', $classNames);
        $this->assertContains('input', $classNames);
        $this->assertContains('icon', $classNames);
        
        // Check IDs
        $idNames = array_column($analysis->ids, 'name');
        $this->assertContains('header', $idNames);
        
        // Check media queries
        $this->assertNotEmpty($analysis->mediaQueries);
    }
    
    public function test_analysis_model_helper_methods()
    {
        $analysis = new CssFileAnalysis('/test/file.css', [
            'classes' => [
                ['name' => 'test-class'],
                ['name' => 'another-class']
            ],
            'ids' => [
                ['name' => 'test-id'],
                ['name' => 'another-id']
            ]
        ]);
        
        $this->assertTrue($analysis->hasClass('test-class'));
        $this->assertFalse($analysis->hasClass('nonexistent-class'));
        
        $this->assertTrue($analysis->hasId('test-id'));
        $this->assertFalse($analysis->hasId('nonexistent-id'));
    }
    
    public function test_parse_file_ignores_comments()
    {
        $cssContent = <<<'CSS'
/* This is a comment with .fake-class and #fake-id */
.real-class {
    /* Another comment with .another-fake */
    color: red;
}

/*
Multi-line comment
with .multi-fake-class
and #multi-fake-id
*/

#real-id {
    background: blue;
}
CSS;
        
        $testFile = $this->testFilesPath . '/with-comments.css';
        File::put($testFile, $cssContent);
        
        $analysis = $this->analyzer->parseFile($testFile);
        
        $classNames = array_column($analysis->classes, 'name');
        $idNames = array_column($analysis->ids, 'name');
        
        // Should find real classes/IDs
        $this->assertContains('real-class', $classNames);
        $this->assertContains('real-id', $idNames);
        
        // Should not find classes/IDs in comments
        $this->assertNotContains('fake-class', $classNames);
        $this->assertNotContains('fake-id', $idNames);
        $this->assertNotContains('another-fake', $classNames);
        $this->assertNotContains('multi-fake-class', $classNames);
        $this->assertNotContains('multi-fake-id', $idNames);
    }
}