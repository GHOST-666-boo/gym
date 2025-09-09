<?php

namespace Tests\Feature\Services\Cleanup;

use App\Services\Cleanup\BladeAnalyzer;
use Tests\TestCase;

class BladeAnalysisIntegrationTest extends TestCase
{
    private BladeAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new BladeAnalyzer();
    }

    public function test_analyze_real_blade_templates(): void
    {
        // Test with actual project templates
        $homeTemplate = resource_path('views/public/home.blade.php');
        $productCardTemplate = resource_path('views/components/product-card.blade.php');
        
        if (!file_exists($homeTemplate) || !file_exists($productCardTemplate)) {
            $this->markTestSkipped('Required template files not found');
        }

        // Analyze the home template
        $homeAnalysis = $this->analyzer->parseTemplate($homeTemplate);
        
        $this->assertNotEmpty($homeAnalysis->components);
        $this->assertNotEmpty($homeAnalysis->variables);
        $this->assertNotEmpty($homeAnalysis->sections);
        
        // Should find the product-card component usage
        $this->assertTrue($homeAnalysis->hasComponent('product-card'));
        
        // Should find common variables
        $this->assertTrue($homeAnalysis->hasVariable('featuredProducts'));

        // Analyze the product card component
        $productCardAnalysis = $this->analyzer->parseTemplate($productCardTemplate);
        
        $this->assertNotEmpty($productCardAnalysis->variables);
        $this->assertTrue($productCardAnalysis->hasVariable('product'));
    }

    public function test_find_unused_components_in_project(): void
    {
        // This test demonstrates the unused component detection
        $unusedComponents = $this->analyzer->findUnusedComponents();
        
        // Should return an array (may be empty if all components are used)
        $this->assertIsArray($unusedComponents);
        
        // Each unused component should be a file path
        foreach ($unusedComponents as $component) {
            $this->assertFileExists($component);
            $this->assertStringContainsString('components', $component);
        }
    }

    public function test_duplicate_detection_across_project_templates(): void
    {
        // Analyze multiple templates to find duplicates
        $templatePaths = [
            resource_path('views/public/home.blade.php'),
            resource_path('views/components/product-card.blade.php'),
        ];
        
        $analyses = [];
        foreach ($templatePaths as $path) {
            if (file_exists($path)) {
                $analyses[] = $this->analyzer->parseTemplate($path);
            }
        }
        
        if (empty($analyses)) {
            $this->markTestSkipped('No template files found for analysis');
        }
        
        $duplicates = $this->analyzer->findDuplicateStructures($analyses);
        
        // Should return an array of duplicates (may be empty)
        $this->assertIsArray($duplicates);
        
        // Each duplicate should have the required structure
        foreach ($duplicates as $duplicate) {
            $this->assertArrayHasKey('occurrences', $duplicate);
            $this->assertArrayHasKey('similarity_score', $duplicate);
            $this->assertArrayHasKey('refactoring_priority', $duplicate);
            $this->assertGreaterThanOrEqual(2, count($duplicate['occurrences']));
        }
    }

    public function test_component_extraction_suggestions(): void
    {
        // Test component extraction suggestions
        $templatePaths = [
            resource_path('views/public/home.blade.php'),
            resource_path('views/components/product-card.blade.php'),
        ];
        
        $analyses = [];
        foreach ($templatePaths as $path) {
            if (file_exists($path)) {
                $analyses[] = $this->analyzer->parseTemplate($path);
            }
        }
        
        if (empty($analyses)) {
            $this->markTestSkipped('No template files found for analysis');
        }
        
        $candidates = $this->analyzer->extractComponentCandidates($analyses);
        
        // Should return an array of component candidates
        $this->assertIsArray($candidates);
        
        // Each candidate should have the required structure
        foreach ($candidates as $candidate) {
            $this->assertArrayHasKey('suggested_name', $candidate);
            $this->assertArrayHasKey('occurrences', $candidate);
            $this->assertArrayHasKey('potential_savings', $candidate);
            $this->assertArrayHasKey('structure', $candidate);
            
            // Suggested name should be a valid component name
            $this->assertStringContainsString('-', $candidate['suggested_name']);
            $this->assertGreaterThan(0, $candidate['potential_savings']);
        }
    }
}