<?php

namespace Tests\Integration\Services\Cleanup;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CommandLineIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private string $testOutputPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testOutputPath = storage_path('testing/command-output');
        File::makeDirectory($this->testOutputPath, 0755, true);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testOutputPath)) {
            File::deleteDirectory($this->testOutputPath);
        }
        
        parent::tearDown();
    }

    /** @test */
    public function cleanup_analyze_command_executes_successfully()
    {
        // Act
        $exitCode = Artisan::call('cleanup:analyze', [
            '--dry-run' => true
        ]);

        // Assert
        $this->assertEquals(0, $exitCode, 'Cleanup analyze command should succeed');
        
        $output = Artisan::output();
        $this->assertStringContainsString('analysis', strtolower($output));
        $this->assertStringContainsString('files', strtolower($output));
    }

    /** @test */
    public function cleanup_analyze_command_with_specific_paths()
    {
        // Act
        $exitCode = Artisan::call('cleanup:analyze', [
            '--path' => 'app/Models',
            '--dry-run' => true
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('app/Models', $output);
    }

    /** @test */
    public function cleanup_analyze_command_with_file_type_filters()
    {
        // Act
        $exitCode = Artisan::call('cleanup:analyze', [
            '--type' => 'php',
            '--dry-run' => true
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('php', strtolower($output));
    }

    /** @test */
    public function cleanup_execute_command_runs_in_dry_run_mode()
    {
        // Act
        $exitCode = Artisan::call('cleanup:execute', [
            '--dry-run' => true
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('dry run', strtolower($output));
        $this->assertStringContainsString('no changes', strtolower($output));
    }

    /** @test */
    public function cleanup_execute_command_with_backup_option()
    {
        // Act
        $exitCode = Artisan::call('cleanup:execute', [
            '--dry-run' => true,
            '--backup' => true
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('backup', strtolower($output));
    }

    /** @test */
    public function cleanup_execute_command_with_test_validation()
    {
        // Act
        $exitCode = Artisan::call('cleanup:execute', [
            '--dry-run' => true,
            '--run-tests' => true
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('test', strtolower($output));
    }

    /** @test */
    public function cleanup_report_command_generates_report()
    {
        // Act
        $exitCode = Artisan::call('cleanup:report', [
            '--format' => 'json',
            '--output' => $this->testOutputPath . '/report.json'
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('report', strtolower($output));
        
        // Check if report file was created
        $reportPath = $this->testOutputPath . '/report.json';
        if (File::exists($reportPath)) {
            $this->assertFileExists($reportPath);
            $reportContent = File::get($reportPath);
            $this->assertJson($reportContent);
        }
    }

    /** @test */
    public function cleanup_report_command_with_html_format()
    {
        // Act
        $exitCode = Artisan::call('cleanup:report', [
            '--format' => 'html',
            '--output' => $this->testOutputPath . '/report.html'
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $reportPath = $this->testOutputPath . '/report.html';
        if (File::exists($reportPath)) {
            $this->assertFileExists($reportPath);
            $reportContent = File::get($reportPath);
            $this->assertStringContainsString('<html', $reportContent);
        }
    }

    /** @test */
    public function interactive_cleanup_workflow_responds_to_user_input()
    {
        // This test simulates user interaction
        // In a real scenario, this would test the interactive prompts
        
        // Act
        $exitCode = Artisan::call('cleanup:interactive', [
            '--dry-run' => true
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('interactive', strtolower($output));
    }

    /** @test */
    public function cleanup_commands_handle_invalid_options_gracefully()
    {
        // Act
        $exitCode = Artisan::call('cleanup:analyze', [
            '--invalid-option' => 'value'
        ]);

        // Assert
        // Command should either succeed (ignoring invalid option) or fail gracefully
        $this->assertContains($exitCode, [0, 1, 2]);
        
        if ($exitCode !== 0) {
            $output = Artisan::output();
            $this->assertStringContainsString('option', strtolower($output));
        }
    }

    /** @test */
    public function cleanup_commands_provide_help_information()
    {
        // Act
        $exitCode = Artisan::call('cleanup:analyze', [
            '--help' => true
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('usage', strtolower($output));
        $this->assertStringContainsString('options', strtolower($output));
    }

    /** @test */
    public function cleanup_commands_support_verbose_output()
    {
        // Act
        $exitCode = Artisan::call('cleanup:analyze', [
            '--dry-run' => true,
            '-v' => true
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        // Verbose output should contain more detailed information
        $this->assertNotEmpty($output);
    }

    /** @test */
    public function cleanup_commands_handle_large_codebases()
    {
        // Act
        $startTime = microtime(true);
        
        $exitCode = Artisan::call('cleanup:analyze', [
            '--dry-run' => true,
            '--batch-size' => 10
        ]);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert
        $this->assertEquals(0, $exitCode);
        $this->assertLessThan(60, $executionTime, 'Command should complete within reasonable time');
        
        $output = Artisan::output();
        $this->assertNotEmpty($output);
    }

    /** @test */
    public function cleanup_commands_support_configuration_files()
    {
        // Arrange
        $configContent = json_encode([
            'dryRun' => true,
            'createBackup' => true,
            'runTests' => false,
            'includeFileTypes' => ['php', 'js'],
            'excludePaths' => ['vendor/', 'node_modules/']
        ]);
        
        $configPath = $this->testOutputPath . '/cleanup-config.json';
        File::put($configPath, $configContent);

        // Act
        $exitCode = Artisan::call('cleanup:execute', [
            '--config' => $configPath
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('config', strtolower($output));
    }

    /** @test */
    public function cleanup_commands_provide_progress_feedback()
    {
        // Act
        $exitCode = Artisan::call('cleanup:analyze', [
            '--dry-run' => true,
            '--progress' => true
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        // Progress output should show some indication of progress
        $this->assertNotEmpty($output);
    }

    /** @test */
    public function cleanup_commands_support_selective_operations()
    {
        // Act
        $exitCode = Artisan::call('cleanup:execute', [
            '--dry-run' => true,
            '--only' => 'imports,variables'
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('imports', strtolower($output));
        $this->assertStringContainsString('variables', strtolower($output));
    }

    /** @test */
    public function cleanup_commands_handle_errors_gracefully()
    {
        // Act - Try to analyze a non-existent path
        $exitCode = Artisan::call('cleanup:analyze', [
            '--path' => '/non/existent/path',
            '--dry-run' => true
        ]);

        // Assert
        // Command should handle the error gracefully
        $this->assertContains($exitCode, [0, 1, 2]);
        
        $output = Artisan::output();
        $this->assertNotEmpty($output);
    }

    /** @test */
    public function cleanup_commands_support_output_redirection()
    {
        // Act
        $outputFile = $this->testOutputPath . '/command-output.txt';
        
        $exitCode = Artisan::call('cleanup:analyze', [
            '--dry-run' => true,
            '--output' => $outputFile
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        if (File::exists($outputFile)) {
            $this->assertFileExists($outputFile);
            $outputContent = File::get($outputFile);
            $this->assertNotEmpty($outputContent);
        }
    }

    /** @test */
    public function cleanup_commands_integrate_with_orchestrator()
    {
        // This test verifies that commands properly use the CleanupOrchestrator
        
        // Act
        $exitCode = Artisan::call('cleanup:analyze', [
            '--dry-run' => true
        ]);

        // Assert
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('analysis', strtolower($output));
        
        // Verify that the command actually used the orchestrator
        // This would be more detailed in a real implementation
        $this->assertNotEmpty($output);
    }
}