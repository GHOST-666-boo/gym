<?php

namespace Tests\Unit\Services\Cleanup;

use Tests\TestCase;
use App\Services\Cleanup\RiskAssessmentEngine;
use App\Services\Cleanup\Models\CleanupPlan;
use App\Services\Cleanup\Models\OperationLog;
use App\Services\Cleanup\Models\RiskAssessment;

class RiskAssessmentEngineTest extends TestCase
{
    private RiskAssessmentEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new RiskAssessmentEngine();
    }

    public function test_assesses_file_deletion_risks_for_many_files()
    {
        $plan = new CleanupPlan();
        $executionResults = ['files_removed' => 25];

        $risks = $this->engine->assessRisks($plan, $executionResults);

        $fileDeletionRisks = array_filter($risks, function ($risk) {
            return $risk->type === 'file_deletion';
        });

        $this->assertNotEmpty($fileDeletionRisks);
        
        $highRisk = array_filter($fileDeletionRisks, function ($risk) {
            return $risk->severity === 'high';
        });
        
        $this->assertNotEmpty($highRisk);
    }

    public function test_assesses_moderate_file_deletion_risks()
    {
        $plan = new CleanupPlan();
        $executionResults = ['files_removed' => 15];

        $risks = $this->engine->assessRisks($plan, $executionResults);

        $fileDeletionRisks = array_filter($risks, function ($risk) {
            return $risk->type === 'file_deletion' && $risk->severity === 'medium';
        });

        $this->assertNotEmpty($fileDeletionRisks);
    }

    public function test_assesses_code_modification_risks()
    {
        $plan = new CleanupPlan();
        $executionResults = [
            'methods_removed' => 12,
            'imports_removed' => 60,
        ];

        $risks = $this->engine->assessRisks($plan, $executionResults);

        $codeModificationRisks = array_filter($risks, function ($risk) {
            return $risk->type === 'code_modification';
        });

        $this->assertNotEmpty($codeModificationRisks);
        
        // Should have risks for both method removal and import removal
        $this->assertGreaterThanOrEqual(2, count($codeModificationRisks));
    }

    public function test_assesses_refactoring_risks()
    {
        $plan = new CleanupPlan();
        $executionResults = [
            'duplicates_refactored' => 8,
            'components_created' => 5,
        ];

        $risks = $this->engine->assessRisks($plan, $executionResults);

        $refactoringRisks = array_filter($risks, function ($risk) {
            return $risk->type === 'refactoring';
        });

        $this->assertNotEmpty($refactoringRisks);
        $this->assertGreaterThanOrEqual(2, count($refactoringRisks));
    }

    public function test_assesses_operational_risks_for_failed_operations()
    {
        $plan = new CleanupPlan();
        $executionResults = ['failed_operations' => 3];

        $risks = $this->engine->assessRisks($plan, $executionResults);

        $operationalRisks = array_filter($risks, function ($risk) {
            return $risk->type === 'operational';
        });

        $this->assertNotEmpty($operationalRisks);
        
        $failedOpRisk = array_filter($operationalRisks, function ($risk) {
            return str_contains($risk->title, 'Failed Cleanup Operations');
        });
        
        $this->assertNotEmpty($failedOpRisk);
    }

    public function test_assesses_operational_risks_for_long_execution_time()
    {
        $plan = new CleanupPlan();
        $executionResults = [];
        
        $operationLog = new OperationLog([
            'operations' => [
                ['execution_time' => 350], // Over 5 minutes
                ['execution_time' => 120],
            ],
        ]);

        $risks = $this->engine->assessRisks($plan, $executionResults, $operationLog);

        $operationalRisks = array_filter($risks, function ($risk) {
            return $risk->type === 'operational' && str_contains($risk->title, 'Long Operation');
        });

        $this->assertNotEmpty($operationalRisks);
    }

    public function test_assesses_testing_risks_for_no_test_data()
    {
        $plan = new CleanupPlan();
        $executionResults = []; // No test data

        $risks = $this->engine->assessRisks($plan, $executionResults);

        $testingRisks = array_filter($risks, function ($risk) {
            return $risk->type === 'testing';
        });

        $this->assertNotEmpty($testingRisks);
        
        $noTestRisk = array_filter($testingRisks, function ($risk) {
            return str_contains($risk->title, 'No Test Validation');
        });
        
        $this->assertNotEmpty($noTestRisk);
    }

    public function test_assesses_testing_risks_for_failed_tests()
    {
        $plan = new CleanupPlan();
        $executionResults = [
            'tests_passed' => 45,
            'tests_total' => 50,
        ];

        $risks = $this->engine->assessRisks($plan, $executionResults);

        $testingRisks = array_filter($risks, function ($risk) {
            return $risk->type === 'testing' && str_contains($risk->title, 'Test Failures');
        });

        $this->assertNotEmpty($testingRisks);
    }

    public function test_identifies_critical_files()
    {
        $plan = new CleanupPlan();
        $plan->filesToDelete = [
            '/app/Models/User.php',
            '/config/app.php', // Critical
            '/public/index.php', // Critical
            '/vendor/package/file.php',
        ];

        $risks = $this->engine->assessRisks($plan, []);

        $criticalFileRisks = array_filter($risks, function ($risk) {
            return $risk->type === 'file_deletion' && 
                   str_contains($risk->title, 'Critical Files') &&
                   !empty($risk->affectedFiles);
        });

        $this->assertNotEmpty($criticalFileRisks);
    }

    public function test_risk_assessment_model_functionality()
    {
        $data = [
            'type' => 'file_deletion',
            'severity' => 'high',
            'title' => 'Test Risk',
            'description' => 'Test description',
            'potential_impact' => 'Test impact',
            'mitigation_strategies' => ['Strategy 1', 'Strategy 2'],
            'likelihood' => 'high',
            'detection_difficulty' => 'high',
        ];

        $risk = new RiskAssessment($data);

        $this->assertEquals('file_deletion', $risk->type);
        $this->assertEquals('high', $risk->severity);
        $this->assertTrue($risk->requiresImmediateAttention());

        $riskScore = $risk->getRiskScore();
        $this->assertGreaterThan(50, $riskScore);
        $this->assertLessThanOrEqual(100, $riskScore);

        $riskLevel = $risk->getRiskLevel();
        $this->assertContains($riskLevel, ['Critical', 'High', 'Medium', 'Low', 'Minimal']);

        $severityInfo = $risk->getSeverityInfo();
        $this->assertEquals('High', $severityInfo['text']);
        $this->assertEquals('orange', $severityInfo['color']);

        $typeInfo = $risk->getTypeInfo();
        $this->assertEquals('File Deletion', $typeInfo['name']);
        $this->assertEquals('Data Loss', $typeInfo['category']);
    }

    public function test_risk_recommended_actions()
    {
        $risk = new RiskAssessment([
            'severity' => 'critical',
            'likelihood' => 'high',
            'mitigation_strategies' => ['Basic strategy'],
        ]);

        $actions = $risk->getRecommendedActions();
        
        $this->assertContains('Basic strategy', $actions);
        $this->assertContains('Stop deployment immediately', $actions);
        $this->assertContains('Escalate to senior team members', $actions);
    }

    public function test_risk_serialization()
    {
        $risk = new RiskAssessment([
            'type' => 'operational',
            'severity' => 'medium',
            'title' => 'Test Risk',
            'likelihood' => 'low',
        ]);

        $array = $risk->toArray();
        $this->assertIsArray($array);
        $this->assertEquals('operational', $array['type']);
        $this->assertEquals('medium', $array['severity']);
        $this->assertArrayHasKey('risk_score', $array);
        $this->assertArrayHasKey('risk_level', $array);
        $this->assertArrayHasKey('requires_immediate_attention', $array);
    }

    public function test_handles_empty_execution_results()
    {
        $plan = new CleanupPlan();
        $executionResults = [];

        $risks = $this->engine->assessRisks($plan, $executionResults);

        $this->assertIsArray($risks);
        // Should still generate at least testing risks for no test data
        $this->assertNotEmpty($risks);
    }
}