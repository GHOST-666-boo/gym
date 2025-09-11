<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\LaravelAnalyzerInterface;
use App\Services\Cleanup\Models\RouteAnalysis;
use App\Services\Cleanup\Models\ModelAnalysis;
use App\Services\Cleanup\Models\MigrationAnalysis;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionMethod;

class LaravelAnalyzer implements LaravelAnalyzerInterface
{
    private $parser;
    private $nodeFinder;
    
    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
        $this->nodeFinder = new NodeFinder;
    }
    
    /**
     * Parse route definitions from Laravel route files
     */
    public function parseRouteDefinitions(array $routeFiles): array
    {
        $routes = [];
        
        foreach ($routeFiles as $routeFile) {
            if (!File::exists($routeFile)) {
                continue;
            }
            
            $routes = array_merge($routes, $this->parseRouteFile($routeFile));
        }
        
        return $routes;
    }
    
    /**
     * Parse a single route file
     */
    private function parseRouteFile(string $filePath): array
    {
        $routes = [];
        $content = File::get($filePath);
        
        try {
            // Use regex to parse routes for better accuracy with chained methods
            $routes = $this->parseRoutesWithRegex($content);
        } catch (\Exception $e) {
            // Log parsing error but continue
            \Log::warning("Failed to parse route file: {$filePath}", ['error' => $e->getMessage()]);
        }
        
        return $routes;
    }
    
    /**
     * Parse routes using regex for better handling of chained methods
     */
    private function parseRoutesWithRegex(string $content): array
    {
        $routes = [];
        
        // Remove comments and normalize whitespace
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);
        $content = preg_replace('/\/\/.*$/m', '', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Find all Route:: calls with their complete definitions
        // This pattern handles both simple routes and routes with closures/chained methods
        $pattern = '/Route::(\w+)\s*\([^}]*?(?:\}[^;]*?)?;/s';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $route = $this->parseRouteDefinition($match[0]);
                if ($route) {
                    $routes[] = $route;
                }
            }
        }
        
        return $routes;
    }
    
    /**
     * Parse a single route definition
     */
    private function parseRouteDefinition(string $routeDefinition): ?RouteAnalysis
    {
        // Extract method
        if (!preg_match('/Route::(\w+)\s*\(/', $routeDefinition, $methodMatch)) {
            return null;
        }
        $method = strtoupper($methodMatch[1]);
        
        // Extract URI
        if (!preg_match('/Route::\w+\s*\(\s*[\'"]([^\'"]+)[\'"]/', $routeDefinition, $uriMatch)) {
            return null;
        }
        $uri = $uriMatch[1];
        
        $controller = '';
        $action = '';
        
        // Extract controller and action
        if (preg_match('/\[([^,]+)::class\s*,\s*[\'"](\w+)[\'"]\]/', $routeDefinition, $arrayMatch)) {
            // Array syntax: [Controller::class, 'method']
            $controllerClass = trim($arrayMatch[1]);
            $action = $arrayMatch[2];
            
            // Add namespace if not present
            if (!str_contains($controllerClass, '\\')) {
                $controller = 'App\\Http\\Controllers\\' . $controllerClass;
            } else {
                $controller = $controllerClass;
            }
        } elseif (preg_match('/[\'"]([^@\'"]+)@(\w+)[\'"]/', $routeDefinition, $stringMatch)) {
            // String syntax: 'Controller@method'
            $controller = $stringMatch[1];
            $action = $stringMatch[2];
        }
        
        // Extract name
        $name = '';
        if (preg_match('/->name\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $routeDefinition, $nameMatch)) {
            $name = $nameMatch[1];
        }
        
        // Extract middleware
        $middleware = [];
        if (preg_match('/->middleware\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $routeDefinition, $middlewareMatch)) {
            $middleware = [$middlewareMatch[1]];
        } elseif (preg_match('/->middleware\s*\(\s*\[([^\]]+)\]\s*\)/', $routeDefinition, $middlewareArrayMatch)) {
            $middlewareStr = $middlewareArrayMatch[1];
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $middlewareStr, $middlewareItems);
            $middleware = $middlewareItems[1] ?? [];
        }
        
        return new RouteAnalysis([
            'name' => $name,
            'uri' => $uri,
            'method' => $method,
            'controller' => $controller,
            'action' => $action,
            'middleware' => $middleware,
            'isUsed' => false
        ]);
    }
    
    /**
     * Find Route:: method calls in AST
     */
    private function findRouteCalls(array $ast): array
    {
        return $this->nodeFinder->find($ast, function (Node $node) {
            return $node instanceof Node\Expr\StaticCall &&
                   $node->class instanceof Node\Name &&
                   $node->class->toString() === 'Route';
        });
    }
    
    /**
     * Extract route information from a Route call node
     */
    private function extractRouteInfo(Node\Expr\StaticCall $routeCall): ?RouteAnalysis
    {
        $method = $routeCall->name->name ?? '';
        $args = $routeCall->args;
        
        if (empty($args)) {
            return null;
        }
        
        $uri = $this->extractStringValue($args[0]->value ?? null);
        $controller = null;
        $action = null;
        $name = null;
        $middleware = [];
        
        // Extract controller and action from second argument
        if (isset($args[1])) {
            $secondArg = $args[1]->value;
            
            if ($secondArg instanceof Node\Expr\Array_) {
                // Array format: [Controller::class, 'method']
                $items = $secondArg->items;
                if (count($items) >= 2 && $items[0] && $items[1]) {
                    $controller = $this->extractControllerClass($items[0]->value ?? null);
                    $action = $this->extractStringValue($items[1]->value ?? null);
                }
            } elseif ($secondArg instanceof Node\Scalar\String_) {
                // String format: 'Controller@method'
                $controllerAction = $secondArg->value;
                if (strpos($controllerAction, '@') !== false) {
                    [$controller, $action] = explode('@', $controllerAction, 2);
                }
            }
        }
        
        // Look for chained method calls (name, middleware, etc.)
        $name = $this->findRouteNameInFile($routeCall);
        $middleware = $this->findRouteMiddlewareInFile($routeCall);
        
        if (!$uri) {
            return null;
        }
        
        return new RouteAnalysis([
            'name' => $name ?? '',
            'uri' => $uri,
            'method' => strtoupper($method),
            'controller' => $controller ?? '',
            'action' => $action ?? '',
            'middleware' => $middleware,
            'isUsed' => false
        ]);
    }
    
    /**
     * Find chained method calls on a route
     */
    private function findChainedCalls(Node $node): array
    {
        $calls = [];
        $current = $node;
        
        // Look for method calls chained on this route
        while ($current && null !== $current->getAttribute('parent')) {
            $parent = $current->getAttribute('parent');
            if ($parent instanceof Node\Expr\MethodCall && $parent->var === $current) {
                $calls[] = [
                    'method' => $parent->name->name ?? '',
                    'args' => array_map(function($arg) {
                        return $arg->value ?? null;
                    }, $parent->args ?? [])
                ];
                $current = $parent;
            } else {
                break;
            }
        }
        
        return $calls;
    }
    
    /**
     * Extract string value from a node
     */
    private function extractStringValue(?Node $node): ?string
    {
        if (!$node) {
            return null;
        }
        
        if ($node instanceof Node\Scalar\String_) {
            return $node->value;
        }
        
        return null;
    }
    
    /**
     * Extract controller class from a node
     */
    private function extractControllerClass(?Node $node): ?string
    {
        if (!$node) {
            return null;
        }
        
        if ($node instanceof Node\Expr\ClassConstFetch) {
            if ($node->class instanceof Node\Name) {
                return $node->class->toString();
            }
        }
        
        return null;
    }
    
    /**
     * Find route name by parsing the file content around the route call
     */
    private function findRouteNameInFile(Node\Expr\StaticCall $routeCall): ?string
    {
        // For now, return null - this would need more complex parsing
        // In a real implementation, we'd need to traverse the parent nodes
        // to find chained ->name() calls
        return null;
    }
    
    /**
     * Find route middleware by parsing the file content around the route call
     */
    private function findRouteMiddlewareInFile(Node\Expr\StaticCall $routeCall): array
    {
        // For now, return empty array - this would need more complex parsing
        // In a real implementation, we'd need to traverse the parent nodes
        // to find chained ->middleware() calls
        return [];
    }
    
    /**
     * Extract middleware from arguments
     */
    private function extractMiddleware(array $args): array
    {
        $middleware = [];
        
        foreach ($args as $arg) {
            if ($arg instanceof Node\Scalar\String_) {
                $middleware[] = $arg->value;
            } elseif ($arg instanceof Node\Expr\Array_) {
                foreach ($arg->items as $item) {
                    if ($item && $item->value instanceof Node\Scalar\String_) {
                        $middleware[] = $item->value->value;
                    }
                }
            }
        }
        
        return $middleware;
    }
    
    /**
     * Analyze controller method usage across the application
     */
    public function analyzeControllerUsage(array $controllerPaths): array
    {
        $controllerMethods = [];
        
        foreach ($controllerPaths as $path) {
            if (is_dir($path)) {
                $controllerMethods = array_merge($controllerMethods, $this->scanControllerDirectory($path));
            } elseif (is_file($path)) {
                $methods = $this->analyzeControllerFile($path);
                if (!empty($methods)) {
                    $controllerMethods = array_merge($controllerMethods, $methods);
                }
            }
        }
        
        return $controllerMethods;
    }
    
    /**
     * Scan a directory for controller files
     */
    private function scanControllerDirectory(string $directory): array
    {
        $controllerMethods = [];
        $files = File::allFiles($directory);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $methods = $this->analyzeControllerFile($file->getPathname());
                if (!empty($methods)) {
                    $controllerMethods = array_merge($controllerMethods, $methods);
                }
            }
        }
        
        return $controllerMethods;
    }
    
    /**
     * Analyze a single controller file
     */
    private function analyzeControllerFile(string $filePath): array
    {
        $methods = [];
        
        try {
            $content = File::get($filePath);
            $ast = $this->parser->parse($content);
            
            $classes = $this->nodeFinder->findInstanceOf($ast, Node\Stmt\Class_::class);
            
            foreach ($classes as $class) {
                $className = $this->getFullClassName($class, $content);
                
                if ($this->isControllerClass($className)) {
                    $classMethods = $this->extractClassMethods($class, $className);
                    $methods = array_merge($methods, $classMethods);
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Failed to analyze controller file: {$filePath}", ['error' => $e->getMessage()]);
        }
        
        return $methods;
    }
    
    /**
     * Get full class name including namespace
     */
    private function getFullClassName(Node\Stmt\Class_ $class, string $content): string
    {
        $className = $class->name->name ?? '';
        
        // Extract namespace from file content
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = trim($matches[1]);
            return $namespace . '\\' . $className;
        }
        
        return $className;
    }
    
    /**
     * Check if a class is a controller
     */
    private function isControllerClass(string $className): bool
    {
        return str_contains($className, 'Controller') || 
               str_contains($className, 'App\\Http\\Controllers');
    }
    
    /**
     * Extract methods from a class
     */
    private function extractClassMethods(Node\Stmt\Class_ $class, string $className): array
    {
        $methods = [];
        
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                $methodName = $stmt->name->name ?? '';
                $visibility = $this->getMethodVisibility($stmt);
                
                // Skip magic methods, constructor, and private methods
                if (!str_starts_with($methodName, '__') && $visibility !== 'private') {
                    $methods[] = [
                        'class' => $className,
                        'method' => $methodName,
                        'visibility' => $visibility,
                        'isUsed' => false
                    ];
                }
            }
        }
        
        return $methods;
    }
    
    /**
     * Get method visibility
     */
    private function getMethodVisibility(Node\Stmt\ClassMethod $method): string
    {
        if ($method->isPrivate()) {
            return 'private';
        } elseif ($method->isProtected()) {
            return 'protected';
        }
        
        return 'public';
    }
    
    /**
     * Find unused routes in the application
     */
    public function findUnusedRoutes(array $routes): array
    {
        $unusedRoutes = [];
        
        foreach ($routes as $route) {
            if (!$route->isUsed && !$this->isSystemRoute($route)) {
                $unusedRoutes[] = $route;
            }
        }
        
        return $unusedRoutes;
    }
    
    /**
     * Check if a route is a system route that should not be marked as unused
     */
    private function isSystemRoute(RouteAnalysis $route): bool
    {
        $systemRoutes = [
            'login', 'logout', 'register', 'password.request', 'password.reset',
            'verification.notice', 'verification.verify', 'verification.send'
        ];
        
        return in_array($route->name, $systemRoutes) ||
               str_contains($route->uri, 'auth') ||
               str_contains($route->controller, 'Auth\\');
    }
    
    /**
     * Find unused controller methods
     */
    public function findUnusedControllerMethods(array $controllerMethods, array $routes): array
    {
        $usedMethods = [];
        
        // Mark methods used by routes
        foreach ($routes as $route) {
            if ($route->controller && $route->action) {
                $key = $route->controller . '@' . $route->action;
                $usedMethods[$key] = true;
            }
        }
        
        $unusedMethods = [];
        
        foreach ($controllerMethods as $method) {
            $key = $method['class'] . '@' . $method['method'];
            
            if (!isset($usedMethods[$key]) && !$this->isSystemMethod($method)) {
                $unusedMethods[] = $method;
            }
        }
        
        return $unusedMethods;
    }
    
    /**
     * Check if a method is a system method that should not be marked as unused
     */
    private function isSystemMethod(array $method): bool
    {
        $systemMethods = [
            '__construct', '__destruct', '__call', '__callStatic',
            'middleware', 'authorize', 'validate'
        ];
        
        return in_array($method['method'], $systemMethods) ||
               $method['visibility'] === 'private' ||
               str_starts_with($method['method'], '__');
    }
    
    /**
     * Analyze Eloquent model usage across the application
     */
    public function analyzeModelUsage(array $modelPaths): array
    {
        $models = [];
        
        foreach ($modelPaths as $path) {
            if (is_dir($path)) {
                $models = array_merge($models, $this->scanModelDirectory($path));
            } elseif (is_file($path)) {
                $model = $this->analyzeModelFile($path);
                if ($model) {
                    $models[] = $model;
                }
            }
        }
        
        return $models;
    }
    
    /**
     * Scan a directory for model files
     */
    private function scanModelDirectory(string $directory): array
    {
        $models = [];
        $files = File::allFiles($directory);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $model = $this->analyzeModelFile($file->getPathname());
                if ($model) {
                    $models[] = $model;
                }
            }
        }
        
        return $models;
    }
    
    /**
     * Analyze a single model file
     */
    private function analyzeModelFile(string $filePath): ?\App\Services\Cleanup\Models\ModelAnalysis
    {
        try {
            $content = File::get($filePath);
            $ast = $this->parser->parse($content);
            
            $classes = $this->nodeFinder->findInstanceOf($ast, Node\Stmt\Class_::class);
            
            foreach ($classes as $class) {
                $className = $this->getFullClassName($class, $content);
                
                if ($this->isModelClass($className, $content)) {
                    return $this->extractModelInfo($class, $className, $filePath, $content);
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Failed to analyze model file: {$filePath}", ['error' => $e->getMessage()]);
        }
        
        return null;
    }
    
    /**
     * Check if a class is an Eloquent model
     */
    private function isModelClass(string $className, string $content): bool
    {
        return str_contains($className, 'App\\Models') ||
               str_contains($content, 'extends Model') ||
               str_contains($content, 'use HasFactory') ||
               str_contains($content, 'Illuminate\\Database\\Eloquent\\Model');
    }
    
    /**
     * Extract model information from a class
     */
    private function extractModelInfo(Node\Stmt\Class_ $class, string $className, string $filePath, string $content): \App\Services\Cleanup\Models\ModelAnalysis
    {
        $tableName = $this->extractTableName($class, $className);
        $relationships = $this->extractRelationships($class);
        
        return new \App\Services\Cleanup\Models\ModelAnalysis([
            'className' => $className,
            'filePath' => $filePath,
            'tableName' => $tableName,
            'relationships' => $relationships,
            'isUsed' => false,
            'usageCount' => 0
        ]);
    }
    
    /**
     * Extract table name from model
     */
    private function extractTableName(Node\Stmt\Class_ $class, string $className): string
    {
        // Look for $table property
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->name->name === 'table' && $prop->default instanceof Node\Scalar\String_) {
                        return $prop->default->value;
                    }
                }
            }
        }
        
        // Default Laravel convention: pluralize class name
        $shortName = basename(str_replace('\\', '/', $className));
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $shortName)) . 's';
    }
    
    /**
     * Extract relationships from model
     */
    private function extractRelationships(Node\Stmt\Class_ $class): array
    {
        $relationships = [];
        
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod) {
                $methodName = $stmt->name->name ?? '';
                
                // Look for relationship methods
                if ($this->isRelationshipMethod($stmt)) {
                    $relationType = $this->getRelationshipType($stmt);
                    $relatedModel = $this->getRelatedModel($stmt);
                    
                    if ($relationType && $relatedModel) {
                        $relationships[] = [
                            'method' => $methodName,
                            'type' => $relationType,
                            'model' => $relatedModel
                        ];
                    }
                }
            }
        }
        
        return $relationships;
    }
    
    /**
     * Check if a method is a relationship method
     */
    private function isRelationshipMethod(Node\Stmt\ClassMethod $method): bool
    {
        $relationshipMethods = [
            'hasOne', 'hasMany', 'belongsTo', 'belongsToMany',
            'morphTo', 'morphOne', 'morphMany', 'morphToMany'
        ];
        
        // Look for return statements with relationship calls
        $returnStmts = $this->nodeFinder->findInstanceOf($method->stmts, Node\Stmt\Return_::class);
        
        foreach ($returnStmts as $returnStmt) {
            if ($returnStmt->expr instanceof Node\Expr\MethodCall) {
                $methodCall = $returnStmt->expr;
                if ($methodCall->name instanceof Node\Identifier) {
                    $methodName = $methodCall->name->name;
                    if (in_array($methodName, $relationshipMethods)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get relationship type from method
     */
    private function getRelationshipType(Node\Stmt\ClassMethod $method): ?string
    {
        $returnStmts = $this->nodeFinder->findInstanceOf($method->stmts, Node\Stmt\Return_::class);
        
        foreach ($returnStmts as $returnStmt) {
            if ($returnStmt->expr instanceof Node\Expr\MethodCall) {
                $methodCall = $returnStmt->expr;
                if ($methodCall->name instanceof Node\Identifier) {
                    return $methodCall->name->name;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get related model from relationship method
     */
    private function getRelatedModel(Node\Stmt\ClassMethod $method): ?string
    {
        $returnStmts = $this->nodeFinder->findInstanceOf($method->stmts, Node\Stmt\Return_::class);
        
        foreach ($returnStmts as $returnStmt) {
            if ($returnStmt->expr instanceof Node\Expr\MethodCall) {
                $methodCall = $returnStmt->expr;
                $args = $methodCall->args;
                
                if (!empty($args) && $args[0]->value instanceof Node\Expr\ClassConstFetch) {
                    $classConst = $args[0]->value;
                    if ($classConst->class instanceof Node\Name) {
                        return $classConst->class->toString();
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Analyze migration files for unused migrations
     */
    public function analyzeMigrations(string $migrationPath): array
    {
        $migrations = [];
        
        if (!is_dir($migrationPath)) {
            return $migrations;
        }
        
        $files = File::files($migrationPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $migration = $this->analyzeMigrationFile($file->getPathname());
                if ($migration) {
                    $migrations[] = $migration;
                }
            }
        }
        
        return $migrations;
    }
    
    /**
     * Analyze a single migration file
     */
    private function analyzeMigrationFile(string $filePath): ?\App\Services\Cleanup\Models\MigrationAnalysis
    {
        try {
            $content = File::get($filePath);
            $fileName = basename($filePath);
            
            // Extract class name
            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                $className = $matches[1];
            } else {
                return null;
            }
            
            // Extract table name and operation
            $tableName = $this->extractMigrationTableName($content);
            $operation = $this->extractMigrationOperation($content);
            
            return new \App\Services\Cleanup\Models\MigrationAnalysis([
                'fileName' => $fileName,
                'filePath' => $filePath,
                'className' => $className,
                'tableName' => $tableName,
                'operation' => $operation,
                'isUsed' => true // Default to used
            ]);
        } catch (\Exception $e) {
            \Log::warning("Failed to analyze migration file: {$filePath}", ['error' => $e->getMessage()]);
        }
        
        return null;
    }
    
    /**
     * Extract table name from migration content
     */
    private function extractMigrationTableName(string $content): string
    {
        // Look for Schema::create, Schema::table, etc.
        if (preg_match('/Schema::(create|table)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            return $matches[2];
        }
        
        return '';
    }
    
    /**
     * Extract migration operation type
     */
    private function extractMigrationOperation(string $content): string
    {
        if (str_contains($content, 'Schema::create')) {
            return 'create';
        } elseif (str_contains($content, 'Schema::table')) {
            return 'alter';
        } elseif (str_contains($content, 'Schema::drop')) {
            return 'drop';
        }
        
        return 'unknown';
    }
    
    /**
     * Find unused Eloquent models
     */
    public function findUnusedModels(array $models, array $controllerFiles, array $bladeFiles): array
    {
        $usedModels = [];
        
        // Check usage in controllers
        foreach ($controllerFiles as $controllerFile) {
            $this->findModelUsageInFile($controllerFile, $models, $usedModels);
        }
        
        // Check usage in blade templates
        foreach ($bladeFiles as $bladeFile) {
            $this->findModelUsageInFile($bladeFile, $models, $usedModels);
        }
        
        $unusedModels = [];
        
        foreach ($models as $model) {
            $className = $model->className ?? $model['className'] ?? '';
            $shortName = basename(str_replace('\\', '/', $className));
            
            if (!isset($usedModels[$className]) && !isset($usedModels[$shortName]) && !$this->isSystemModel($className)) {
                $unusedModels[] = $model;
            }
        }
        
        return $unusedModels;
    }
    
    /**
     * Find model usage in a file
     */
    private function findModelUsageInFile(string $filePath, array $models, array &$usedModels): void
    {
        if (!File::exists($filePath)) {
            return;
        }
        
        $content = File::get($filePath);
        
        foreach ($models as $model) {
            $className = $model->className ?? $model['className'] ?? '';
            $shortName = basename(str_replace('\\', '/', $className));
            
            // Check for various usage patterns
            if (str_contains($content, $className) ||
                str_contains($content, $shortName . '::') ||
                str_contains($content, 'new ' . $shortName) ||
                str_contains($content, $shortName . '->') ||
                preg_match('/\b' . preg_quote($shortName, '/') . '\b/', $content)) {
                $usedModels[$className] = true;
                $usedModels[$shortName] = true;
            }
        }
    }
    
    /**
     * Check if a model is a system model that should not be marked as unused
     */
    private function isSystemModel(string $className): bool
    {
        $systemModels = [
            'User', 'App\\Models\\User',
            'Model', 'Illuminate\\Database\\Eloquent\\Model'
        ];
        
        return in_array($className, $systemModels) ||
               str_contains($className, 'Pivot') ||
               str_contains($className, 'Auth');
    }
    
    /**
     * Find unused migration files
     */
    public function findUnusedMigrations(array $migrations): array
    {
        $unusedMigrations = [];
        
        foreach ($migrations as $migration) {
            // For now, we consider migrations unused if they are very old and don't have corresponding models
            // This is a conservative approach since migrations are generally important
            if ($this->isMigrationPotentiallyUnused($migration)) {
                $unusedMigrations[] = $migration;
            }
        }
        
        return $unusedMigrations;
    }
    
    /**
     * Check if a migration is potentially unused (very conservative)
     */
    private function isMigrationPotentiallyUnused(\App\Services\Cleanup\Models\MigrationAnalysis $migration): bool
    {
        // Only consider drop migrations as potentially unused
        // And only if they're for tables that don't exist in current models
        return $migration->isDropTable() && !$migration->hasCorrespondingModel;
    }
}