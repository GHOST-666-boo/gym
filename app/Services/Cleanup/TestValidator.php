<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\TestValidatorInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TestValidator implements TestValidatorInterface
{
    private array $testResults = [];
    private array $dynamicUsagePatterns = [
        'call_user_func',
        'call_user_func_array',
        'variable_function',
        'ReflectionClass',
        'ReflectionMethod',
        'ReflectionFunction',
        'class_exists',
        'method_exists',
        'function_exists',
        'is_callable',
        '$$', // Variable variables
        'eval(',
        'create_function',
    ];

    public function runValidationTests(): array
    {
        Log::info('Starting validation test suite');
        
        $results = [
            'unit_tests' => $this->runTestSuite('Unit'),
            'feature_tests' => $this->runTestSuite('Feature'),
            'integration_tests' => $this->runTestSuite('Integration'),
            'critical_paths' => $this->validateCriticalPaths(),
            'database_integrity' => $this->validateDatabaseIntegrity(),
            'runtime_errors' => $this->checkRuntimeErrors(),
        ];
        
        $this->testResults = $results;
        
        Log::info('Validation test suite completed', [
            'results_summary' => $this->summarizeResults($results)
        ]);
        
        return $results;
    }

    public function verifyFunctionality(array $removedElements): bool
    {
        Log::info('Verifying functionality after code removal', [
            'removed_count' => count($removedElements)
        ]);
        
        // Run core functionality tests
        $coreTests = $this->runCoreTests();
        
        // Check for any failures related to removed elements
        $failures = $this->analyzeTestFailures($coreTests, $removedElements);
        
        if (!empty($failures)) {
            Log::warning('Functionality verification failed', [
                'failures' => $failures
            ]);
            return false;
        }
        
        Log::info('Functionality verification passed');
        return true;
    }

    public function checkDynamicUsage(array $codeElements): array
    {
        Log::info('Checking for dynamic code usage patterns');
        
        $dynamicUsages = [];
        
        foreach ($codeElements as $element) {
            $filePath = $element['file'] ?? null;
            $elementName = $element['name'] ?? null;
            
            if (!$filePath || !file_exists($filePath)) {
                continue;
            }
            
            $content = file_get_contents($filePath);
            $usages = $this->findDynamicUsageInContent($content, $elementName);
            
            if (!empty($usages)) {
                $dynamicUsages[] = [
                    'file' => $filePath,
                    'element' => $elementName,
                    'usages' => $usages,
                    'risk_level' => $this->assessRiskLevel($usages)
                ];
            }
        }
        
        Log::info('Dynamic usage check completed', [
            'found_usages' => count($dynamicUsages)
        ]);
        
        return $dynamicUsages;
    }

    public function runTestSuite(string $suite): array
    {
        try {
            $output = [];
            $exitCode = 0;
            
            // Run PHPUnit tests for specific suite
            $command = "php artisan test --testsuite={$suite} --stop-on-failure";
            exec($command . ' 2>&1', $output, $exitCode);
            
            $result = [
                'suite' => $suite,
                'exit_code' => $exitCode,
                'output' => implode("\n", $output),
                'passed' => $exitCode === 0,
                'timestamp' => now()->toISOString()
            ];
            
            if ($exitCode !== 0) {
                Log::warning("Test suite {$suite} failed", [
                    'exit_code' => $exitCode,
                    'output' => $result['output']
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to run test suite {$suite}: " . $e->getMessage());
            
            return [
                'suite' => $suite,
                'exit_code' => -1,
                'output' => $e->getMessage(),
                'passed' => false,
                'timestamp' => now()->toISOString()
            ];
        }
    }

    public function validateCriticalPaths(): bool
    {
        Log::info('Validating critical application paths');
        
        $criticalTests = [
            'database_connection' => $this->testDatabaseConnection(),
            'cache_system' => $this->testCacheSystem(),
            'file_system' => $this->testFileSystem(),
            'authentication' => $this->testAuthentication(),
            'routing' => $this->testRouting(),
        ];
        
        $allPassed = true;
        foreach ($criticalTests as $test => $result) {
            if (!$result) {
                Log::error("Critical path validation failed: {$test}");
                $allPassed = false;
            }
        }
        
        return $allPassed;
    }

    public function checkRuntimeErrors(): array
    {
        Log::info('Checking for runtime errors');
        
        $errors = [];
        
        // Check Laravel logs for recent errors
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $recentErrors = $this->parseRecentLogErrors($logPath);
            $errors = array_merge($errors, $recentErrors);
        }
        
        // Check for PHP syntax errors in modified files
        $syntaxErrors = $this->checkSyntaxErrors();
        $errors = array_merge($errors, $syntaxErrors);
        
        return $errors;
    }

    public function validateDatabaseIntegrity(): bool
    {
        try {
            Log::info('Validating database integrity');
            
            // Test database connection
            DB::connection()->getPdo();
            
            // Run basic queries to ensure tables are accessible
            $tables = ['users', 'products', 'categories']; // Add your critical tables
            
            foreach ($tables as $table) {
                try {
                    DB::table($table)->count();
                } catch (\Exception $e) {
                    Log::error("Database integrity check failed for table: {$table}", [
                        'error' => $e->getMessage()
                    ]);
                    return false;
                }
            }
            
            Log::info('Database integrity validation passed');
            return true;
        } catch (\Exception $e) {
            Log::error('Database integrity validation failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getTestResults(): array
    {
        return $this->testResults;
    }

    private function runCoreTests(): array
    {
        // Run essential tests that verify core functionality
        return [
            $this->runTestSuite('Unit'),
            $this->runTestSuite('Feature')
        ];
    }

    private function analyzeTestFailures(array $testResults, array $removedElements): array
    {
        $failures = [];
        
        foreach ($testResults as $result) {
            if (!$result['passed']) {
                // Analyze if failure is related to removed elements
                $relatedFailures = $this->findRelatedFailures($result['output'], $removedElements);
                $failures = array_merge($failures, $relatedFailures);
            }
        }
        
        return $failures;
    }

    private function findRelatedFailures(string $output, array $removedElements): array
    {
        $failures = [];
        
        foreach ($removedElements as $element) {
            $elementName = $element['name'] ?? '';
            if ($elementName && strpos($output, $elementName) !== false) {
                $failures[] = [
                    'element' => $elementName,
                    'type' => $element['type'] ?? 'unknown',
                    'file' => $element['file'] ?? 'unknown'
                ];
            }
        }
        
        return $failures;
    }

    private function findDynamicUsageInContent(string $content, string $elementName): array
    {
        $usages = [];
        
        foreach ($this->dynamicUsagePatterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                // Check if the pattern is used with the element name
                if (strpos($content, $elementName) !== false) {
                    $usages[] = [
                        'pattern' => $pattern,
                        'context' => $this->extractContext($content, $pattern, $elementName)
                    ];
                }
            }
        }
        
        return $usages;
    }

    private function extractContext(string $content, string $pattern, string $elementName): string
    {
        $lines = explode("\n", $content);
        $context = '';
        
        foreach ($lines as $lineNum => $line) {
            if (strpos($line, $pattern) !== false && strpos($line, $elementName) !== false) {
                $start = max(0, $lineNum - 2);
                $end = min(count($lines) - 1, $lineNum + 2);
                
                for ($i = $start; $i <= $end; $i++) {
                    $context .= ($i + 1) . ": " . $lines[$i] . "\n";
                }
                break;
            }
        }
        
        return $context;
    }

    private function assessRiskLevel(array $usages): string
    {
        $highRiskPatterns = ['eval(', 'create_function', '$$'];
        
        foreach ($usages as $usage) {
            if (in_array($usage['pattern'], $highRiskPatterns)) {
                return 'high';
            }
        }
        
        return count($usages) > 2 ? 'medium' : 'low';
    }

    private function testDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function testCacheSystem(): bool
    {
        try {
            cache()->put('test_key', 'test_value', 60);
            $value = cache()->get('test_key');
            cache()->forget('test_key');
            return $value === 'test_value';
        } catch (\Exception $e) {
            return false;
        }
    }

    private function testFileSystem(): bool
    {
        try {
            $testFile = storage_path('test_file_' . uniqid() . '.txt');
            file_put_contents($testFile, 'test');
            $content = file_get_contents($testFile);
            unlink($testFile);
            return $content === 'test';
        } catch (\Exception $e) {
            return false;
        }
    }

    private function testAuthentication(): bool
    {
        try {
            // Test that auth system is accessible
            auth()->guard('web');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function testRouting(): bool
    {
        try {
            // Test that route system is working
            $routes = app('router')->getRoutes();
            return $routes->count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function parseRecentLogErrors(string $logPath): array
    {
        $errors = [];
        $cutoffTime = now()->subMinutes(10); // Check last 10 minutes
        
        try {
            $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_reverse($lines); // Start from most recent
            
            foreach ($lines as $line) {
                if (strpos($line, '[' . $cutoffTime->format('Y-m-d')) !== false) {
                    if (strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false) {
                        $errors[] = [
                            'message' => $line,
                            'type' => 'log_error'
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore file reading errors
        }
        
        return array_slice($errors, 0, 10); // Return max 10 recent errors
    }

    private function checkSyntaxErrors(): array
    {
        $errors = [];
        
        // Check PHP files for syntax errors
        $phpFiles = glob(app_path() . '/**/*.php', GLOB_BRACE);
        
        foreach (array_slice($phpFiles, 0, 50) as $file) { // Limit to 50 files for performance
            $output = [];
            $exitCode = 0;
            
            exec("php -l \"{$file}\" 2>&1", $output, $exitCode);
            
            if ($exitCode !== 0) {
                $errors[] = [
                    'file' => $file,
                    'message' => implode("\n", $output),
                    'type' => 'syntax_error'
                ];
            }
        }
        
        return $errors;
    }

    private function summarizeResults(array $results): array
    {
        $summary = [
            'total_tests' => 0,
            'passed_tests' => 0,
            'failed_tests' => 0,
            'critical_paths_ok' => $results['critical_paths'] ?? false,
            'database_ok' => $results['database_integrity'] ?? false,
            'runtime_errors_count' => count($results['runtime_errors'] ?? [])
        ];
        
        foreach (['unit_tests', 'feature_tests', 'integration_tests'] as $testType) {
            if (isset($results[$testType])) {
                $summary['total_tests']++;
                if ($results[$testType]['passed']) {
                    $summary['passed_tests']++;
                } else {
                    $summary['failed_tests']++;
                }
            }
        }
        
        return $summary;
    }
}