<?php

namespace Tests\Unit\Services\Cleanup;

use App\Services\Cleanup\Contracts\BackupManagerInterface;
use App\Services\Cleanup\Contracts\TestValidatorInterface;
use App\Services\Cleanup\SafetyValidationService;
use Mockery;
use Tests\TestCase;

class SafetyValidationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_validate_before_cleanup()
    {
        // Arrange
        $mockBackupManager = Mockery::mock(BackupManagerInterface::class);
        $mockTestValidator = Mockery::mock(TestValidatorInterface::class);
        $safetyService = new SafetyValidationService($mockBackupManager, $mockTestValidator);

        $cleanupPlan = [
            [
                'type' => 'remove_unused_methods',
                'elements' => [
                    ['file' => 'app/Models/User.php', 'name' => 'unusedMethod']
                ]
            ]
        ];

        $mockBackupManager->shouldReceive('createBackup')
            ->once()
            ->with('Pre-cleanup safety backup')
            ->andReturn('backup-123');

        $mockBackupManager->shouldReceive('canRollback')
            ->once()
            ->with('backup-123')
            ->andReturn(true);

        $mockTestValidator->shouldReceive('checkDynamicUsage')
            ->twice()
            ->andReturn([]);

        $mockTestValidator->shouldReceive('runValidationTests')
            ->once()
            ->andReturn([
                'unit_tests' => ['passed' => true],
                'feature_tests' => ['passed' => true],
                'critical_paths' => true,
                'database_integrity' => true,
                'runtime_errors' => []
            ]);

        $mockTestValidator->shouldReceive('validateCriticalPaths')
            ->once()
            ->andReturn(true);

        // Act
        $result = $safetyService->validateBeforeCleanup($cleanupPlan);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('backup_created', $result);
        $this->assertArrayHasKey('dynamic_usage_check', $result);
        $this->assertArrayHasKey('test_results', $result);
        $this->assertArrayHasKey('safety_score', $result);
        $this->assertArrayHasKey('safe_to_proceed', $result);
        $this->assertTrue($result['backup_created']);
        $this->assertTrue($result['safe_to_proceed']);
    }

    public function test_can_validate_after_cleanup()
    {
        // Arrange
        $mockBackupManager = Mockery::mock(BackupManagerInterface::class);
        $mockTestValidator = Mockery::mock(TestValidatorInterface::class);
        $safetyService = new SafetyValidationService($mockBackupManager, $mockTestValidator);

        $executedOperations = [
            ['name' => 'unusedMethod', 'type' => 'method', 'file' => 'app/Models/User.php']
        ];

        $mockTestValidator->shouldReceive('verifyFunctionality')
            ->once()
            ->with($executedOperations)
            ->andReturn(true);

        $mockTestValidator->shouldReceive('runValidationTests')
            ->once()
            ->andReturn([
                'unit_tests' => ['passed' => true],
                'feature_tests' => ['passed' => true],
                'critical_paths' => true
            ]);

        $mockTestValidator->shouldReceive('checkRuntimeErrors')
            ->once()
            ->andReturn([]);

        // Act
        $result = $safetyService->validateAfterCleanup($executedOperations);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('functionality_verified', $result);
        $this->assertArrayHasKey('test_results', $result);
        $this->assertArrayHasKey('runtime_errors', $result);
        $this->assertArrayHasKey('rollback_required', $result);
        $this->assertArrayHasKey('validation_passed', $result);
        $this->assertTrue($result['functionality_verified']);
        $this->assertFalse($result['rollback_required']);
        $this->assertTrue($result['validation_passed']);
    }

    public function test_can_create_safety_checkpoint()
    {
        // Arrange
        $mockBackupManager = Mockery::mock(BackupManagerInterface::class);
        $mockTestValidator = Mockery::mock(TestValidatorInterface::class);
        $safetyService = new SafetyValidationService($mockBackupManager, $mockTestValidator);

        $operation = 'Remove unused imports';
        $metadata = ['files_count' => 5];

        $mockBackupManager->shouldReceive('createCheckpoint')
            ->once()
            ->with($operation, $metadata)
            ->andReturn('checkpoint-123');

        // Act
        $checkpointId = $safetyService->createSafetyCheckpoint($operation, $metadata);

        // Assert
        $this->assertEquals('checkpoint-123', $checkpointId);
    }

    public function test_can_rollback_to_safety_checkpoint()
    {
        // Arrange
        $mockBackupManager = Mockery::mock(BackupManagerInterface::class);
        $mockTestValidator = Mockery::mock(TestValidatorInterface::class);
        $safetyService = new SafetyValidationService($mockBackupManager, $mockTestValidator);

        $checkpointId = 'checkpoint-123';

        $mockBackupManager->shouldReceive('rollbackToCheckpoint')
            ->once()
            ->with($checkpointId)
            ->andReturn(true);

        // Act
        $result = $safetyService->rollbackToSafetyCheckpoint($checkpointId);

        // Assert
        $this->assertTrue($result);
    }

    public function test_can_get_safety_report()
    {
        // Arrange
        $mockBackupManager = Mockery::mock(BackupManagerInterface::class);
        $mockTestValidator = Mockery::mock(TestValidatorInterface::class);
        $safetyService = new SafetyValidationService($mockBackupManager, $mockTestValidator);

        // Act
        $report = $safetyService->getSafetyReport();

        // Assert
        $this->assertIsArray($report);
        $this->assertArrayHasKey('report_generated', $report);
        $this->assertArrayHasKey('last_backup_id', $report);
        $this->assertArrayHasKey('checkpoints_created', $report);
        $this->assertArrayHasKey('validations', $report);
        $this->assertArrayHasKey('summary', $report);
    }

    public function test_is_safe_to_cleanup_returns_false_for_high_risk_plan()
    {
        // Arrange
        $mockBackupManager = Mockery::mock(BackupManagerInterface::class);
        $mockTestValidator = Mockery::mock(TestValidatorInterface::class);
        $safetyService = new SafetyValidationService($mockBackupManager, $mockTestValidator);

        $cleanupPlan = [
            [
                'type' => 'remove_unused_methods',
                'elements' => [
                    ['file' => 'app/Models/User.php', 'name' => 'riskyMethod']
                ]
            ]
        ];

        $mockTestValidator->shouldReceive('checkDynamicUsage')
            ->once()
            ->andReturn([
                [
                    'file' => 'app/Models/User.php',
                    'element' => 'riskyMethod',
                    'risk_level' => 'high',
                    'usages' => [['pattern' => 'eval(']]
                ]
            ]);

        // Act
        $result = $safetyService->isSafeToCleanup($cleanupPlan);

        // Assert
        $this->assertFalse($result);
    }

    public function test_emergency_rollback_fails_when_no_backup_available()
    {
        // Arrange
        $mockBackupManager = Mockery::mock(BackupManagerInterface::class);
        $mockTestValidator = Mockery::mock(TestValidatorInterface::class);
        $safetyService = new SafetyValidationService($mockBackupManager, $mockTestValidator);

        // Act
        $result = $safetyService->emergencyRollback();

        // Assert
        $this->assertFalse($result);
    }

    public function test_validate_after_cleanup_requires_rollback_on_failure()
    {
        // Arrange
        $mockBackupManager = Mockery::mock(BackupManagerInterface::class);
        $mockTestValidator = Mockery::mock(TestValidatorInterface::class);
        $safetyService = new SafetyValidationService($mockBackupManager, $mockTestValidator);

        $executedOperations = [
            ['name' => 'brokenMethod', 'type' => 'method', 'file' => 'app/Models/User.php']
        ];

        $mockTestValidator->shouldReceive('verifyFunctionality')
            ->once()
            ->with($executedOperations)
            ->andReturn(false); // Functionality verification failed

        $mockTestValidator->shouldReceive('runValidationTests')
            ->once()
            ->andReturn([
                'unit_tests' => ['passed' => false],
                'critical_paths' => false
            ]);

        $mockTestValidator->shouldReceive('checkRuntimeErrors')
            ->once()
            ->andReturn(['error' => 'Runtime error detected']);

        // Act
        $result = $safetyService->validateAfterCleanup($executedOperations);

        // Assert
        $this->assertFalse($result['functionality_verified']);
        $this->assertTrue($result['rollback_required']);
        $this->assertFalse($result['validation_passed']);
    }
}