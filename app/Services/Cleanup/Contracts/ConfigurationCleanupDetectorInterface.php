<?php

namespace App\Services\Cleanup\Contracts;

interface ConfigurationCleanupDetectorInterface
{
    /**
     * Detect unused configuration options across all config files
     */
    public function detectUnusedConfigOptions(): array;
    
    /**
     * Track environment variable usage throughout the application
     */
    public function trackEnvironmentVariableUsage(): array;
    
    /**
     * Generate cleanup suggestions for configuration files
     */
    public function generateConfigCleanupSuggestions(): array;
    
    /**
     * Validate that a configuration option can be safely removed
     */
    public function validateConfigOptionRemoval(string $configKey): bool;
    
    /**
     * Find unused environment variables
     */
    public function findUnusedEnvironmentVariables(): array;
}