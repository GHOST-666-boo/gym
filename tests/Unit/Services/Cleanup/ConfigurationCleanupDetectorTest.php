<?php

namespace Tests\Unit\Services\Cleanup;

use App\Services\Cleanup\ConfigurationCleanupDetector;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Mockery;

class ConfigurationCleanupDetectorTest extends TestCase
{
    private ConfigurationCleanupDetector $detector;
    private string $testDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new ConfigurationCleanupDetector();
        $this->testDirectory = storage_path('testing/config_cleanup');
        
        // Create test directory
        if (!File::exists($this->testDirectory)) {
            File::makeDirectory($this->testDirectory, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists($this->testDirectory)) {
            File::deleteDirectory($this->testDirectory);
        }
        
        Mockery::close();
        parent::tearDown();
    }

    public function test_validateConfigOptionRemoval_protects_framework_configs()
    {
        $protectedConfigs = [
            'app.name',
            'app.env',
            'app.debug',
            'app.url',
            'app.key',
            'database.default',
            'database.connections.mysql.host',
            'cache.default',
            'session.driver',
            'queue.default',
            'mail.default',
            'auth.defaults.guard',
            'broadcasting.default',
            'cors.paths',
            'filesystems.default',
            'hashing.driver',
            'logging.default',
            'sanctum.stateful',
            'services.mailgun.domain',
            'view.compiled',
        ];
        
        foreach ($protectedConfigs as $config) {
            $this->assertFalse(
                $this->detector->validateConfigOptionRemoval($config),
                "Protected config {$config} should not be removable"
            );
        }
    }

    public function test_validateConfigOptionRemoval_allows_custom_configs()
    {
        $customConfigs = [
            'custom.setting',
            'myapp.feature_flag',
            'third_party.api_key',
            'analytics.tracking_id',
        ];
        
        foreach ($customConfigs as $config) {
            $this->assertTrue(
                $this->detector->validateConfigOptionRemoval($config),
                "Custom config {$config} should be removable"
            );
        }
    }

    public function test_detectUnusedConfigOptions_finds_unused_options()
    {
        // Test the method exists and returns an array
        $unusedOptions = $this->detector->detectUnusedConfigOptions();
        $this->assertIsArray($unusedOptions);
        
        // Each item should have the expected structure
        foreach ($unusedOptions as $option) {
            $this->assertArrayHasKey('key', $option);
            $this->assertArrayHasKey('file', $option);
            $this->assertArrayHasKey('safe_to_remove', $option);
            $this->assertIsString($option['key']);
            $this->assertIsString($option['file']);
            $this->assertIsBool($option['safe_to_remove']);
        }
    }

    public function test_findUnusedEnvironmentVariables_identifies_unused_vars()
    {
        // Test the method exists and returns an array
        $unusedVars = $this->detector->findUnusedEnvironmentVariables();
        $this->assertIsArray($unusedVars);
        
        // Each item should have the expected structure
        foreach ($unusedVars as $var) {
            $this->assertArrayHasKey('variable', $var);
            $this->assertArrayHasKey('safe_to_remove', $var);
            $this->assertIsString($var['variable']);
            $this->assertIsBool($var['safe_to_remove']);
        }
    }

    public function test_generateConfigCleanupSuggestions_returns_comprehensive_suggestions()
    {
        $suggestions = $this->detector->generateConfigCleanupSuggestions();
        
        $this->assertIsArray($suggestions);
        
        // Each suggestion should have the expected structure
        foreach ($suggestions as $suggestion) {
            $this->assertArrayHasKey('type', $suggestion);
            $this->assertArrayHasKey('description', $suggestion);
            $this->assertArrayHasKey('items', $suggestion);
            $this->assertArrayHasKey('estimated_cleanup', $suggestion);
            $this->assertIsString($suggestion['type']);
            $this->assertIsString($suggestion['description']);
            $this->assertIsArray($suggestion['items']);
            $this->assertIsString($suggestion['estimated_cleanup']);
        }
    }

    public function test_trackEnvironmentVariableUsage_scans_all_relevant_files()
    {
        $envUsage = $this->detector->trackEnvironmentVariableUsage();
        
        $this->assertIsArray($envUsage);
        
        // Each entry should map env var name to array of files
        foreach ($envUsage as $envVar => $files) {
            $this->assertIsString($envVar);
            $this->assertIsArray($files);
        }
    }

    public function test_config_usage_pattern_detection()
    {
        $testContent = "
            <?php
            \$value1 = config('app.name');
            \$value2 = Config::get('database.default', 'mysql');
            \$value3 = \$config['custom.setting'];
        ";
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('scanFileForConfigUsage');
        $method->setAccessible(true);
        
        // Create a temporary file
        $testFile = $this->testDirectory . '/test_usage.php';
        File::put($testFile, $testContent);
        
        $method->invoke($this->detector, $testFile);
        
        // Access private property to check results
        $property = $reflection->getProperty('configUsage');
        $property->setAccessible(true);
        $configUsage = $property->getValue($this->detector);
        
        $this->assertArrayHasKey('app.name', $configUsage);
        $this->assertArrayHasKey('database.default', $configUsage);
        $this->assertArrayHasKey('custom.setting', $configUsage);
    }

    public function test_env_usage_pattern_detection()
    {
        $testContent = "
            <?php
            \$value1 = env('DATABASE_URL');
            \$value2 = \$_ENV['API_KEY'];
            \$value3 = getenv('DEBUG_MODE');
        ";
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('scanFileForEnvUsage');
        $method->setAccessible(true);
        
        // Create a temporary file
        $testFile = $this->testDirectory . '/test_env_usage.php';
        File::put($testFile, $testContent);
        
        $method->invoke($this->detector, $testFile);
        
        // Access private property to check results
        $property = $reflection->getProperty('envUsage');
        $property->setAccessible(true);
        $envUsage = $property->getValue($this->detector);
        
        $this->assertArrayHasKey('DATABASE_URL', $envUsage);
        $this->assertArrayHasKey('API_KEY', $envUsage);
        $this->assertArrayHasKey('DEBUG_MODE', $envUsage);
    }

    public function test_protected_environment_variables()
    {
        $protectedVars = [
            'APP_NAME',
            'APP_ENV',
            'APP_KEY',
            'APP_DEBUG',
            'APP_URL',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
        ];
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('isProtectedEnvVar');
        $method->setAccessible(true);
        
        foreach ($protectedVars as $var) {
            $this->assertTrue(
                $method->invoke($this->detector, $var),
                "Environment variable {$var} should be protected"
            );
        }
        
        // Test non-protected variables
        $customVars = ['CUSTOM_API_KEY', 'THIRD_PARTY_TOKEN', 'ANALYTICS_ID'];
        foreach ($customVars as $var) {
            $this->assertFalse(
                $method->invoke($this->detector, $var),
                "Environment variable {$var} should not be protected"
            );
        }
    }

    public function test_framework_usage_detection()
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('mightBeUsedInFramework');
        $method->setAccessible(true);
        
        $frameworkKeys = [
            'APP_NAME',
            'DB_CONNECTION',
            'CACHE_DRIVER',
            'SESSION_DRIVER',
            'QUEUE_CONNECTION',
            'MAIL_MAILER',
            'AWS_ACCESS_KEY_ID',
            'PUSHER_APP_ID',
            'MIX_APP_NAME',
            'VITE_APP_NAME',
        ];
        
        foreach ($frameworkKeys as $key) {
            $this->assertTrue(
                $method->invoke($this->detector, $key),
                "Key {$key} should be detected as framework-related"
            );
        }
        
        $customKeys = ['CUSTOM_SETTING', 'THIRD_PARTY_API', 'ANALYTICS_TOKEN'];
        foreach ($customKeys as $key) {
            $this->assertFalse(
                $method->invoke($this->detector, $key),
                "Key {$key} should not be detected as framework-related"
            );
        }
    }
}