<?php

namespace Tests\Unit\Services\Cleanup;

use Tests\TestCase;
use App\Services\Cleanup\MaintenanceRecommendationEngine;
use App\Services\Cleanup\Models\CleanupPlan;
use App\Services\Cleanup\Models\MaintenanceRecommendation;

class MaintenanceRecommendationEngineTest extends TestCase
{
    private MaintenanceRecommendationEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new MaintenanceRecommendationEngine();
    }

    public function test_generates_code_organization_recommendations_for_many_removed_files()
    {
        $plan = new CleanupPlan();
        $executionResults = ['files_removed' => 15];

        $recommendations = $this->engine->generateRecommendations($plan, $executionResults);

        $codeOrgRecommendations = array_filter($recommendations, function ($rec) {
            return $rec->type === 'code_organization';
        });

        $this->assertNotEmpty($codeOrgRecommendations);
        
        $scheduleRecommendation = array_filter($codeOrgRecommendations, function ($rec) {
            return str_contains($rec->title, 'Regular Code Cleanup Schedule');
        });
        
        $this->assertNotEmpty($scheduleRecommendation);
    }

    public function test_generates_component_extraction_recommendations()
    {
        $plan = new CleanupPlan();
        $executionResults = ['components_created' => 3];

        $recommendations = $this->engine->generateRecommendations($plan, $executionResults);

        $componentRecommendations = array_filter($recommendations, function ($rec) {
            return str_contains($rec->title, 'Component Extraction Guidelines');
        });

        $this->assertNotEmpty($componentRecommendations);
    }

    public function test_generates_namespace_organization_recommendations()
    {
        $plan = new CleanupPlan();
        $executionResults = ['imports_removed' => 25];

        $recommendations = $this->engine->generateRecommendations($plan, $executionResults);

        $namespaceRecommendations = array_filter($recommendations, function ($rec) {
            return str_contains($rec->title, 'Namespace Organization');
        });

        $this->assertNotEmpty($namespaceRecommendations);
    }

    public function test_generates_performance_recommendations()
    {
        $plan = new CleanupPlan();
        $executionResults = ['methods_removed' => 8];

        $recommendations = $this->engine->generateRecommendations($plan, $executionResults);

        $performanceRecommendations = array_filter($recommendations, function ($rec) {
            return $rec->type === 'performance';
        });

        $this->assertNotEmpty($performanceRecommendations);
        
        // Should always include asset optimization
        $assetRecommendations = array_filter($performanceRecommendations, function ($rec) {
            return str_contains($rec->title, 'Asset Loading');
        });
        
        $this->assertNotEmpty($assetRecommendations);
    }

    public function test_generates_quality_assurance_recommendations()
    {
        $plan = new CleanupPlan();
        $executionResults = [];

        $recommendations = $this->engine->generateRecommendations($plan, $executionResults);

        $qaRecommendations = array_filter($recommendations, function ($rec) {
            return $rec->type === 'quality_assurance';
        });

        $this->assertNotEmpty($qaRecommendations);
        
        // Should always include testing improvements
        $testingRecommendations = array_filter($qaRecommendations, function ($rec) {
            return str_contains($rec->title, 'Testing Coverage');
        });
        
        $this->assertNotEmpty($testingRecommendations);
    }

    public function test_generates_development_process_recommendations()
    {
        $plan = new CleanupPlan();
        $executionResults = [];

        $recommendations = $this->engine->generateRecommendations($plan, $executionResults);

        $processRecommendations = array_filter($recommendations, function ($rec) {
            return $rec->type === 'development_process';
        });

        $this->assertNotEmpty($processRecommendations);
    }

    public function test_generates_monitoring_recommendations()
    {
        $plan = new CleanupPlan();
        $executionResults = [];

        $recommendations = $this->engine->generateRecommendations($plan, $executionResults);

        $monitoringRecommendations = array_filter($recommendations, function ($rec) {
            return $rec->type === 'monitoring';
        });

        $this->assertNotEmpty($monitoringRecommendations);
    }

    public function test_maintenance_recommendation_model_functionality()
    {
        $data = [
            'type' => 'performance',
            'priority' => 'high',
            'title' => 'Test Recommendation',
            'description' => 'Test description',
            'action_items' => ['Action 1', 'Action 2'],
            'estimated_effort' => '4-6 hours',
            'expected_benefit' => 'Test benefit',
        ];

        $recommendation = new MaintenanceRecommendation($data);

        $this->assertEquals('performance', $recommendation->type);
        $this->assertEquals('high', $recommendation->priority);
        $this->assertEquals(3, $recommendation->getPriorityLevel());
        $this->assertTrue($recommendation->isUrgent());

        $formattedPriority = $recommendation->getFormattedPriority();
        $this->assertEquals('High Priority', $formattedPriority['text']);
        $this->assertEquals('red', $formattedPriority['color']);

        $categoryInfo = $recommendation->getCategoryInfo();
        $this->assertEquals('Performance', $categoryInfo['name']);
        $this->assertEquals('⚡', $categoryInfo['icon']);

        $estimatedHours = $recommendation->getEstimatedHours();
        $this->assertEquals(4, $estimatedHours['min']);
        $this->assertEquals(6, $estimatedHours['max']);
    }

    public function test_recommendation_serialization()
    {
        $recommendation = new MaintenanceRecommendation([
            'type' => 'code_organization',
            'priority' => 'medium',
            'title' => 'Test Title',
            'estimated_effort' => '2-3 hours',
        ]);

        $array = $recommendation->toArray();
        $this->assertIsArray($array);
        $this->assertEquals('code_organization', $array['type']);
        $this->assertEquals('medium', $array['priority']);
        $this->assertEquals(2, $array['priority_level']);
        $this->assertFalse($array['is_urgent']);
    }

    public function test_handles_empty_execution_results()
    {
        $plan = new CleanupPlan();
        $executionResults = [];

        $recommendations = $this->engine->generateRecommendations($plan, $executionResults);

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
        
        // Should still generate basic recommendations
        $this->assertGreaterThan(5, count($recommendations));
    }
}