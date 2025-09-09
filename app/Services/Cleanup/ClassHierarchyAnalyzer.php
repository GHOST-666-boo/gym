<?php

namespace App\Services\Cleanup;

use App\Models\Cleanup\PhpFileAnalysis;

class ClassHierarchyAnalyzer
{
    private array $classHierarchy = [];
    private array $methodUsageMap = [];
    private array $allAnalyses = [];

    /**
     * Build class hierarchy from multiple file analyses
     */
    public function buildHierarchy(array $analyses): void
    {
        $this->allAnalyses = $analyses;
        $this->classHierarchy = [];
        $this->methodUsageMap = [];

        // First pass: collect all classes and their inheritance relationships
        foreach ($analyses as $analysis) {
            foreach ($analysis->classes as $class) {
                $className = $this->getFullClassName($class['name'], $analysis->namespace);
                
                $this->classHierarchy[$className] = [
                    'name' => $className,
                    'extends' => $class['extends'] ? $this->resolveClassName($class['extends'], $analysis) : null,
                    'implements' => array_map(fn($interface) => $this->resolveClassName($interface, $analysis), $class['implements']),
                    'methods' => [],
                    'filePath' => $analysis->filePath
                ];
            }
        }

        // Second pass: collect methods for each class
        foreach ($analyses as $analysis) {
            foreach ($analysis->methods as $method) {
                if ($method['class']) {
                    $className = $this->getFullClassName($method['class'], $analysis->namespace);
                    if (isset($this->classHierarchy[$className])) {
                        $this->classHierarchy[$className]['methods'][] = $method;
                    }
                }
            }
        }
    }

    /**
     * Find all method calls across the codebase
     */
    public function findMethodUsage(): array
    {
        $methodCalls = [];

        foreach ($this->allAnalyses as $analysis) {
            $calls = $this->extractMethodCallsFromFile($analysis);
            $methodCalls = array_merge($methodCalls, $calls);
        }

        return $methodCalls;
    }

    /**
     * Check if a method is used anywhere in the hierarchy
     */
    public function isMethodUsed(string $className, string $methodName, array $methodCalls): bool
    {
        // Direct usage check
        $methodKey = "{$className}::{$methodName}";
        if (in_array($methodKey, $methodCalls)) {
            return true;
        }

        // Check if method is called on parent classes (polymorphism)
        $parentClasses = $this->getParentClasses($className);
        foreach ($parentClasses as $parentClass) {
            $parentMethodKey = "{$parentClass}::{$methodName}";
            if (in_array($parentMethodKey, $methodCalls)) {
                return true;
            }
        }

        // Check if method is called on child classes
        $childClasses = $this->getChildClasses($className);
        foreach ($childClasses as $childClass) {
            $childMethodKey = "{$childClass}::{$methodName}";
            if (in_array($childMethodKey, $methodCalls)) {
                return true;
            }
        }

        // Check for interface method implementations
        if ($this->isInterfaceMethod($className, $methodName)) {
            return true;
        }

        // Check for magic method calls or dynamic calls
        if ($this->isMagicMethod($methodName) || $this->hasDynamicCalls($methodName)) {
            return true;
        }

        return false;
    }

    /**
     * Get all parent classes for a given class
     */
    public function getParentClasses(string $className): array
    {
        $parents = [];
        $current = $className;

        while (isset($this->classHierarchy[$current]) && $this->classHierarchy[$current]['extends']) {
            $parent = $this->classHierarchy[$current]['extends'];
            $parents[] = $parent;
            $current = $parent;
        }

        return $parents;
    }

    /**
     * Get all child classes for a given class
     */
    public function getChildClasses(string $className): array
    {
        $children = [];

        foreach ($this->classHierarchy as $class) {
            if ($class['extends'] === $className) {
                $children[] = $class['name'];
                // Recursively get children of children
                $children = array_merge($children, $this->getChildClasses($class['name']));
            }
        }

        return array_unique($children);
    }

    /**
     * Check if method is required by an interface
     */
    private function isInterfaceMethod(string $className, string $methodName): bool
    {
        if (!isset($this->classHierarchy[$className])) {
            return false;
        }

        $class = $this->classHierarchy[$className];
        
        // Check implemented interfaces
        foreach ($class['implements'] as $interface) {
            if (isset($this->classHierarchy[$interface])) {
                foreach ($this->classHierarchy[$interface]['methods'] as $method) {
                    if ($method['name'] === $methodName) {
                        return true;
                    }
                }
            }
        }

        // Check parent class interfaces
        $parentClasses = $this->getParentClasses($className);
        foreach ($parentClasses as $parentClass) {
            if ($this->isInterfaceMethod($parentClass, $methodName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if method is a magic method
     */
    private function isMagicMethod(string $methodName): bool
    {
        $magicMethods = [
            '__construct', '__destruct', '__call', '__callStatic', '__get', '__set',
            '__isset', '__unset', '__sleep', '__wakeup', '__serialize', '__unserialize',
            '__toString', '__invoke', '__set_state', '__clone', '__debugInfo'
        ];

        return in_array($methodName, $magicMethods);
    }

    /**
     * Check for dynamic method calls that might use this method
     */
    private function hasDynamicCalls(string $methodName): bool
    {
        // This is a simplified check - in a real implementation, you'd want to
        // analyze the code for variable method calls, call_user_func, etc.
        foreach ($this->allAnalyses as $analysis) {
            $content = file_get_contents($analysis->filePath);
            
            // Check for variable method calls like $obj->$methodName()
            if (preg_match('/\$\w+\s*->\s*\$\w+\s*\(/', $content)) {
                return true;
            }
            
            // Check for call_user_func with method name
            if (strpos($content, "call_user_func") !== false && strpos($content, $methodName) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract method calls from a file analysis
     */
    private function extractMethodCallsFromFile(PhpFileAnalysis $analysis): array
    {
        $methodCalls = [];
        
        try {
            $parser = (new \PhpParser\ParserFactory)->createForNewestSupportedVersion();
            $code = file_get_contents($analysis->filePath);
            $ast = $parser->parse($code);
            
            if ($ast) {
                $visitor = new MethodCallExtractor($analysis->namespace, $analysis->useStatements);
                $traverser = new \PhpParser\NodeTraverser();
                $traverser->addVisitor($visitor);
                $traverser->traverse($ast);
                
                $methodCalls = $visitor->getMethodCalls();
            }
        } catch (\Exception $e) {
            // Log error but continue processing
        }

        return $methodCalls;
    }

    /**
     * Get full class name including namespace
     */
    private function getFullClassName(string $className, ?string $namespace): string
    {
        if (strpos($className, '\\') !== false) {
            return $className; // Already fully qualified
        }

        return $namespace ? "{$namespace}\\{$className}" : $className;
    }

    /**
     * Resolve class name using namespace and use statements
     */
    private function resolveClassName(string $className, PhpFileAnalysis $analysis): string
    {
        // If already fully qualified, return as is
        if (strpos($className, '\\') === 0) {
            return substr($className, 1);
        }

        // Check use statements for alias resolution
        foreach ($analysis->useStatements as $use) {
            $alias = $use['alias'] ?? $this->getShortClassName($use['name']);
            if ($alias === $className) {
                return $use['name'];
            }
        }

        // If not found in use statements, assume it's in the same namespace
        return $analysis->namespace ? "{$analysis->namespace}\\{$className}" : $className;
    }

    /**
     * Get short class name from fully qualified name
     */
    private function getShortClassName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * Get the class hierarchy for debugging
     */
    public function getHierarchy(): array
    {
        return $this->classHierarchy;
    }
}