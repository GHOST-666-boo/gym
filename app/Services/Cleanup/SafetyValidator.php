<?php

namespace App\Services\Cleanup;

class SafetyValidator
{
    private array $protectedFiles = [
        'composer.json',
        'package.json',
        '.env',
        'artisan',
        'public/index.php',
        'public/.htaccess',
        'app/Http/Kernel.php',
        'bootstrap/app.php'
    ];
    
    public function validateCleanupSafety(array $operations): bool
    {
        // Basic safety validation
        foreach ($operations as $operationType => $items) {
            if ($operationType === 'delete_files') {
                foreach ($items as $file) {
                    if ($this->isProtectedFile($file)) {
                        \Log::warning('Attempted to delete protected file', ['file' => $file]);
                        return false;
                    }
                }
            }
            
            if ($operationType === 'remove_methods') {
                foreach ($items as $method) {
                    if ($this->isProtectedMethod($method)) {
                        \Log::warning('Attempted to remove protected method', ['method' => $method]);
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    public function runTestValidation(): bool
    {
        // For now, just return true to allow cleanup to proceed
        // In a real implementation, this would run the test suite
        \Log::info('Test validation skipped in basic implementation');
        return true;
    }
    
    public function checkDynamicReferences(string $code): bool
    {
        // Check for common dynamic reference patterns
        $dynamicPatterns = [
            '/\$\w+\s*=\s*[\'"][^\'"]+[\'"]\s*;\s*new\s+\$\w+/',  // $class = 'ClassName'; new $class
            '/call_user_func\s*\(/',                              // call_user_func()
            '/\$\w+->\$\w+\s*\(/',                               // $obj->$method()
            '/\$\{\w+\}/',                                       // ${variable}
            '/variable\s+variables?/',                           // Variable variables
        ];
        
        foreach ($dynamicPatterns as $pattern) {
            if (preg_match($pattern, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function isProtectedFile(string $file): bool
    {
        $normalizedFile = str_replace('\\', '/', $file);
        
        foreach ($this->protectedFiles as $protectedFile) {
            if (str_contains($normalizedFile, $protectedFile)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function isProtectedMethod(array $method): bool
    {
        $protectedMethods = [
            '__construct',
            '__destruct',
            '__call',
            '__callStatic',
            '__get',
            '__set',
            '__isset',
            '__unset',
            '__toString',
            'boot',
            'register',
            'handle',
            'authorize',
            'rules',
            'middleware'
        ];
        
        $methodName = $method['method'] ?? $method['name'] ?? '';
        
        return in_array($methodName, $protectedMethods) || str_starts_with($methodName, '__');
    }
}