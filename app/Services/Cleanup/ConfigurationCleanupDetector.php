<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\ConfigurationCleanupDetectorInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ConfigurationCleanupDetector implements ConfigurationCleanupDetectorInterface
{
    private array $configUsage = [];
    private array $envUsage = [];
    private array $protectedConfigKeys = [
        'app.name',
        'app.env',
        'app.debug',
        'app.url',
        'app.key',
        'database.default',
        'database.connections',
        'cache.default',
        'session.driver',
        'queue.default',
        'mail.default',
        'filesystems.default',
        'logging.default',
    ];

    public function detectUnusedConfigOptions(): array
    {
        $this->scanConfigUsage();
        
        $allConfigOptions = $this->getAllConfigOptions();
        $unusedOptions = [];
        
        foreach ($allConfigOptions as $configKey => $configFile) {
            if (!$this->isConfigOptionUsed($configKey) && !$this->isProtectedConfig($configKey)) {
                $unusedOptions[] = [
                    'key' => $configKey,
                    'file' => $configFile,
                    'safe_to_remove' => $this->validateConfigOptionRemoval($configKey),
                ];
            }
        }
        
        return $unusedOptions;
    }

    public function trackEnvironmentVariableUsage(): array
    {
        $this->scanEnvironmentVariableUsage();
        return $this->envUsage;
    }

    public function generateConfigCleanupSuggestions(): array
    {
        $suggestions = [];
        
        // Find unused config options
        $unusedConfigs = $this->detectUnusedConfigOptions();
        if (!empty($unusedConfigs)) {
            $suggestions[] = [
                'type' => 'unused_config_options',
                'description' => 'Remove unused configuration options',
                'items' => $unusedConfigs,
                'estimated_cleanup' => count($unusedConfigs) . ' config options',
            ];
        }
        
        // Find unused environment variables
        $unusedEnvVars = $this->findUnusedEnvironmentVariables();
        if (!empty($unusedEnvVars)) {
            $suggestions[] = [
                'type' => 'unused_env_variables',
                'description' => 'Remove unused environment variables',
                'items' => $unusedEnvVars,
                'estimated_cleanup' => count($unusedEnvVars) . ' environment variables',
            ];
        }
        
        // Find duplicate config values
        $duplicateConfigs = $this->findDuplicateConfigValues();
        if (!empty($duplicateConfigs)) {
            $suggestions[] = [
                'type' => 'duplicate_config_values',
                'description' => 'Consolidate duplicate configuration values',
                'items' => $duplicateConfigs,
                'estimated_cleanup' => count($duplicateConfigs) . ' duplicate values',
            ];
        }
        
        return $suggestions;
    }

    public function validateConfigOptionRemoval(string $configKey): bool
    {
        // Don't remove protected configuration keys
        if ($this->isProtectedConfig($configKey)) {
            return false;
        }
        
        // Don't remove Laravel framework configs that might be used internally
        $frameworkConfigs = [
            'auth.',
            'broadcasting.',
            'cache.',
            'cors.',
            'database.',
            'filesystems.',
            'hashing.',
            'logging.',
            'mail.',
            'queue.',
            'sanctum.',
            'services.',
            'session.',
            'view.',
        ];
        
        foreach ($frameworkConfigs as $frameworkConfig) {
            if (Str::startsWith($configKey, $frameworkConfig)) {
                return false;
            }
        }
        
        // Check if config might be used in middleware or service providers
        if ($this->mightBeUsedInFramework($configKey)) {
            return false;
        }
        
        return true;
    }

    public function findUnusedEnvironmentVariables(): array
    {
        $this->scanEnvironmentVariableUsage();
        
        $envVariables = $this->getEnvironmentVariables();
        $unusedVars = [];
        
        foreach ($envVariables as $envVar) {
            if (!$this->isEnvironmentVariableUsed($envVar) && !$this->isProtectedEnvVar($envVar)) {
                $unusedVars[] = [
                    'variable' => $envVar,
                    'safe_to_remove' => $this->validateEnvVarRemoval($envVar),
                ];
            }
        }
        
        return $unusedVars;
    }

    private function scanConfigUsage(): void
    {
        $this->configUsage = [];
        
        // Scan PHP files for config() calls
        $this->scanPhpFilesForConfig();
        
        // Scan Blade templates for config usage
        $this->scanBladeTemplatesForConfig();
        
        // Scan JavaScript files for config usage (if passed to frontend)
        $this->scanJavaScriptFilesForConfig();
    }

    private function scanEnvironmentVariableUsage(): void
    {
        $this->envUsage = [];
        
        // Scan config files for env() calls
        $this->scanConfigFilesForEnv();
        
        // Scan PHP files for env() calls
        $this->scanPhpFilesForEnv();
        
        // Scan other files that might use env vars
        $this->scanOtherFilesForEnv();
    }

    private function scanPhpFilesForConfig(): void
    {
        $directories = [
            app_path(),
            config_path(),
            database_path(),
            resource_path('views'),
        ];
        
        foreach ($directories as $directory) {
            if (File::isDirectory($directory)) {
                $files = File::allFiles($directory);
                foreach ($files as $file) {
                    if ($file->getExtension() === 'php') {
                        $this->scanFileForConfigUsage($file->getPathname());
                    }
                }
            }
        }
    }

    private function scanBladeTemplatesForConfig(): void
    {
        $bladeFiles = File::allFiles(resource_path('views'));
        
        foreach ($bladeFiles as $file) {
            if ($file->getExtension() === 'php') {
                $this->scanFileForConfigUsage($file->getPathname());
            }
        }
    }

    private function scanJavaScriptFilesForConfig(): void
    {
        $jsDirectories = [
            public_path('js'),
            resource_path('js'),
        ];
        
        foreach ($jsDirectories as $directory) {
            if (File::isDirectory($directory)) {
                $files = File::allFiles($directory);
                foreach ($files as $file) {
                    if (in_array($file->getExtension(), ['js', 'ts', 'jsx', 'tsx'])) {
                        $this->scanFileForConfigUsage($file->getPathname());
                    }
                }
            }
        }
    }

    private function scanFileForConfigUsage(string $filePath): void
    {
        $content = File::get($filePath);
        
        // Patterns for config usage
        $patterns = [
            // config() helper function
            '/config\s*\(\s*[\'"]([^\'\"]+)[\'"]\s*(?:,\s*[^)]+)?\)/',
            // Config facade
            '/Config::get\s*\(\s*[\'"]([^\'\"]+)[\'"]\s*(?:,\s*[^)]+)?\)/',
            // Array access on config
            '/\$config\s*\[\s*[\'"]([^\'\"]+)[\'"]\s*\]/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $configKey) {
                    $this->addConfigUsage($configKey, $filePath);
                }
            }
        }
    }

    private function scanConfigFilesForEnv(): void
    {
        $configFiles = File::allFiles(config_path());
        
        foreach ($configFiles as $file) {
            if ($file->getExtension() === 'php') {
                $this->scanFileForEnvUsage($file->getPathname());
            }
        }
    }

    private function scanPhpFilesForEnv(): void
    {
        $directories = [
            app_path(),
            database_path(),
        ];
        
        foreach ($directories as $directory) {
            if (File::isDirectory($directory)) {
                $files = File::allFiles($directory);
                foreach ($files as $file) {
                    if ($file->getExtension() === 'php') {
                        $this->scanFileForEnvUsage($file->getPathname());
                    }
                }
            }
        }
    }

    private function scanOtherFilesForEnv(): void
    {
        $files = [
            base_path('.env'),
            base_path('.env.example'),
            base_path('docker-compose.yml'),
            base_path('Dockerfile'),
        ];
        
        foreach ($files as $file) {
            if (File::exists($file)) {
                $this->scanFileForEnvUsage($file);
            }
        }
    }

    private function scanFileForEnvUsage(string $filePath): void
    {
        $content = File::get($filePath);
        
        // Patterns for environment variable usage
        $patterns = [
            // env() helper function
            '/env\s*\(\s*[\'"]([^\'\"]+)[\'"]\s*(?:,\s*[^)]+)?\)/',
            // $_ENV access
            '/\$_ENV\s*\[\s*[\'"]([^\'\"]+)[\'"]\s*\]/',
            // getenv() function
            '/getenv\s*\(\s*[\'"]([^\'\"]+)[\'"]\s*\)/',
            // Environment variable definitions in .env files
            '/^([A-Z_][A-Z0-9_]*)\s*=/m',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $envVar) {
                    $this->addEnvUsage($envVar, $filePath);
                }
            }
        }
    }

    private function addConfigUsage(string $configKey, string $filePath): void
    {
        if (!isset($this->configUsage[$configKey])) {
            $this->configUsage[$configKey] = [];
        }
        
        if (!in_array($filePath, $this->configUsage[$configKey])) {
            $this->configUsage[$configKey][] = $filePath;
        }
    }

    private function addEnvUsage(string $envVar, string $filePath): void
    {
        if (!isset($this->envUsage[$envVar])) {
            $this->envUsage[$envVar] = [];
        }
        
        if (!in_array($filePath, $this->envUsage[$envVar])) {
            $this->envUsage[$envVar][] = $filePath;
        }
    }

    private function getAllConfigOptions(): array
    {
        $configOptions = [];
        $configFiles = File::allFiles(config_path());
        
        foreach ($configFiles as $file) {
            if ($file->getExtension() === 'php') {
                $configName = $file->getFilenameWithoutExtension();
                $configArray = include $file->getPathname();
                
                if (is_array($configArray)) {
                    $this->extractConfigKeys($configArray, $configName, $configOptions, $file->getPathname());
                }
            }
        }
        
        return $configOptions;
    }

    private function extractConfigKeys(array $config, string $prefix, array &$configOptions, string $filePath): void
    {
        foreach ($config as $key => $value) {
            $fullKey = $prefix . '.' . $key;
            $configOptions[$fullKey] = $filePath;
            
            if (is_array($value)) {
                $this->extractConfigKeys($value, $fullKey, $configOptions, $filePath);
            }
        }
    }

    private function getEnvironmentVariables(): array
    {
        $envVars = [];
        
        // Get from .env file
        $envFile = base_path('.env');
        if (File::exists($envFile)) {
            $content = File::get($envFile);
            if (preg_match_all('/^([A-Z_][A-Z0-9_]*)\s*=/m', $content, $matches)) {
                $envVars = array_merge($envVars, $matches[1]);
            }
        }
        
        // Get from .env.example file
        $envExampleFile = base_path('.env.example');
        if (File::exists($envExampleFile)) {
            $content = File::get($envExampleFile);
            if (preg_match_all('/^([A-Z_][A-Z0-9_]*)\s*=/m', $content, $matches)) {
                $envVars = array_merge($envVars, $matches[1]);
            }
        }
        
        return array_unique($envVars);
    }

    private function isConfigOptionUsed(string $configKey): bool
    {
        return isset($this->configUsage[$configKey]) && !empty($this->configUsage[$configKey]);
    }

    private function isEnvironmentVariableUsed(string $envVar): bool
    {
        return isset($this->envUsage[$envVar]) && !empty($this->envUsage[$envVar]);
    }

    private function isProtectedConfig(string $configKey): bool
    {
        foreach ($this->protectedConfigKeys as $protectedKey) {
            if (Str::startsWith($configKey, $protectedKey)) {
                return true;
            }
        }
        
        return false;
    }

    private function isProtectedEnvVar(string $envVar): bool
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
        
        return in_array($envVar, $protectedVars);
    }

    private function validateEnvVarRemoval(string $envVar): bool
    {
        return !$this->isProtectedEnvVar($envVar) && !$this->mightBeUsedInFramework($envVar);
    }

    private function mightBeUsedInFramework(string $key): bool
    {
        // Check if the key might be used by Laravel framework internally
        $frameworkPatterns = [
            '/^APP_/',
            '/^DB_/',
            '/^CACHE_/',
            '/^SESSION_/',
            '/^QUEUE_/',
            '/^MAIL_/',
            '/^AWS_/',
            '/^PUSHER_/',
            '/^MIX_/',
            '/^VITE_/',
        ];
        
        foreach ($frameworkPatterns as $pattern) {
            if (preg_match($pattern, $key)) {
                return true;
            }
        }
        
        return false;
    }

    private function findDuplicateConfigValues(): array
    {
        $duplicates = [];
        $configValues = [];
        
        $configFiles = File::allFiles(config_path());
        
        foreach ($configFiles as $file) {
            if ($file->getExtension() === 'php') {
                $configArray = include $file->getPathname();
                if (is_array($configArray)) {
                    $this->findDuplicateValues($configArray, $file->getFilenameWithoutExtension(), $configValues, $duplicates);
                }
            }
        }
        
        return $duplicates;
    }

    private function findDuplicateValues(array $config, string $prefix, array &$configValues, array &$duplicates): void
    {
        foreach ($config as $key => $value) {
            $fullKey = $prefix . '.' . $key;
            
            if (is_array($value)) {
                $this->findDuplicateValues($value, $fullKey, $configValues, $duplicates);
            } elseif (is_string($value) || is_numeric($value)) {
                $valueKey = md5(serialize($value));
                
                if (!isset($configValues[$valueKey])) {
                    $configValues[$valueKey] = [];
                }
                
                $configValues[$valueKey][] = $fullKey;
                
                if (count($configValues[$valueKey]) > 1) {
                    $duplicates[$valueKey] = [
                        'value' => $value,
                        'keys' => $configValues[$valueKey],
                    ];
                }
            }
        }
    }
}