<?php

namespace App\Services\Cleanup;

use App\Services\Cleanup\Contracts\PhpAnalyzerInterface;
use App\Services\Cleanup\Models\PhpFileAnalysis;
use App\Services\Cleanup\DuplicateDetector;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class PhpAnalyzer implements PhpAnalyzerInterface
{
    private $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    public function parseFile(string $filePath): PhpFileAnalysis
    {
        if (!file_exists($filePath)) {
            return new PhpFileAnalysis(
                filePath: $filePath,
                errors: ["File does not exist: {$filePath}"]
            );
        }

        $code = file_get_contents($filePath);
        
        try {
            $ast = $this->parser->parse($code);
            
            if ($ast === null) {
                return new PhpFileAnalysis(
                    filePath: $filePath,
                    errors: ["Failed to parse file: {$filePath}"]
                );
            }

            return $this->analyzeAst($filePath, $ast);
            
        } catch (Error $error) {
            return new PhpFileAnalysis(
                filePath: $filePath,
                errors: ["Parse error in {$filePath}: " . $error->getMessage()]
            );
        }
    }

    private function analyzeAst(string $filePath, array $ast): PhpFileAnalysis
    {
        $visitor = new PhpAnalysisVisitor();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $analysis = new PhpFileAnalysis($filePath, $code, $ast);
        
        // Set namespace if found
        if ($visitor->getNamespace()) {
            $analysis->setNamespace($visitor->getNamespace());
        }
        
        // Add use statements
        foreach ($visitor->getUseStatements() as $import => $line) {
            $analysis->addUseStatement($import, $line);
        }
        
        // Add classes
        foreach ($visitor->getClasses() as $className => $classData) {
            $analysis->addClass($className, $classData['line'] ?? 0);
        }
        
        // Add methods
        foreach ($visitor->getMethods() as $method) {
            $analysis->addMethod(
                $method['class'] ?? '',
                $method['name'] ?? '',
                $method['visibility'] ?? 'public',
                $method['isStatic'] ?? false,
                $method['line'] ?? 0
            );
        }
        
        // Add functions
        foreach ($visitor->getFunctions() as $functionName => $functionData) {
            $analysis->addFunction($functionName, $functionData['line'] ?? 0);
        }
        
        // Add variables
        foreach ($visitor->getVariables() as $variableName => $lines) {
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $analysis->addVariable($variableName, $line);
                }
            } else {
                $analysis->addVariable($variableName, $lines);
            }
        }
        
        return $analysis;
    }

    public function findUnusedImports(PhpFileAnalysis $analysis): array
    {
        $unusedImports = [];
        $usedClasses = $this->extractUsedClasses($analysis);
        
        foreach ($analysis->useStatements as $useStatement) {
            $importedClass = $useStatement['name'];
            $alias = $useStatement['alias'] ?? $this->getShortClassName($importedClass);
            
            // Check if the imported class or its alias is used
            if (!$this->isClassUsed($importedClass, $alias, $usedClasses)) {
                $unusedImports[] = [
                    'name' => $importedClass,
                    'alias' => $useStatement['alias'],
                    'line' => $useStatement['line'],
                    'reason' => 'Class not referenced in file'
                ];
            }
        }
        
        return $unusedImports;
    }

    /**
     * Extract all class names that are actually used in the code
     */
    private function extractUsedClasses(PhpFileAnalysis $analysis): array
    {
        $usedClasses = [];
        
        // Add classes from extends and implements
        foreach ($analysis->classes as $class) {
            if ($class['extends']) {
                $usedClasses[] = $class['extends'];
                $usedClasses[] = $this->getShortClassName($class['extends']);
            }
            foreach ($class['implements'] as $interface) {
                $usedClasses[] = $interface;
                $usedClasses[] = $this->getShortClassName($interface);
            }
        }
        
        // Parse the file again to find all class usages in the code
        $code = file_get_contents($analysis->filePath);
        $ast = $this->parser->parse($code);
        
        if ($ast) {
            $usageVisitor = new ClassUsageVisitor();
            $traverser = new NodeTraverser();
            $traverser->addVisitor($usageVisitor);
            $traverser->traverse($ast);
            
            $usedClasses = array_merge($usedClasses, $usageVisitor->getUsedClasses());
        }
        
        return array_unique($usedClasses);
    }

    /**
     * Check if a class is used in the code
     */
    private function isClassUsed(string $fullClassName, string $alias, array $usedClasses): bool
    {
        $shortName = $this->getShortClassName($fullClassName);
        
        // Check if used by full name, short name, or alias
        return in_array($fullClassName, $usedClasses) ||
               in_array($shortName, $usedClasses) ||
               in_array($alias, $usedClasses);
    }

    /**
     * Get the short class name from a fully qualified class name
     */
    private function getShortClassName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * Extract all method calls that are actually used in the code
     */
    private function extractUsedMethods(PhpFileAnalysis $analysis): array
    {
        $usedMethods = [];
        
        // Parse the file again to find all method calls
        $code = file_get_contents($analysis->filePath);
        $ast = $this->parser->parse($code);
        
        if ($ast) {
            $methodUsageVisitor = new MethodUsageVisitor();
            $traverser = new NodeTraverser();
            $traverser->addVisitor($methodUsageVisitor);
            $traverser->traverse($ast);
            
            $usedMethods = $methodUsageVisitor->getUsedMethods();
        }
        
        return $usedMethods;
    }

    /**
     * Generate a unique key for a method
     */
    private function getMethodKey(?string $className, string $methodName): string
    {
        return $className ? "{$className}::{$methodName}" : $methodName;
    }

    /**
     * Check if a method is used in the code
     */
    private function isMethodUsed(string $methodKey, array $usedMethods): bool
    {
        return in_array($methodKey, $usedMethods);
    }

    /**
     * Find unused variables within methods and functions
     */
    public function findUnusedVariables(PhpFileAnalysis $analysis): array
    {
        $unusedVariables = [];
        
        // Parse the file to analyze variable usage within each method/function
        $code = file_get_contents($analysis->filePath);
        $ast = $this->parser->parse($code);
        
        if ($ast) {
            $variableUsageVisitor = new VariableUsageVisitor();
            $traverser = new NodeTraverser();
            $traverser->addVisitor($variableUsageVisitor);
            $traverser->traverse($ast);
            
            $unusedVariables = $variableUsageVisitor->getUnusedVariables();
        }
        
        return $unusedVariables;
    }

    /**
     * Find unused variables across multiple files
     */
    public function findUnusedVariablesAcrossFiles(array $analyses): array
    {
        $allUnusedVariables = [];
        
        foreach ($analyses as $analysis) {
            $unusedVariables = $this->findUnusedVariables($analysis);
            
            // Add file path to each unused variable for better tracking
            foreach ($unusedVariables as &$variable) {
                $variable['filePath'] = $analysis->filePath;
            }
            
            $allUnusedVariables = array_merge($allUnusedVariables, $unusedVariables);
        }
        
        return $allUnusedVariables;
    }

    /**
     * Remove unused imports from a PHP file
     */
    public function removeUnusedImports(string $filePath, array $unusedImports): bool
    {
        if (empty($unusedImports)) {
            return true;
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        
        // Sort by line number in descending order to avoid line number shifts
        usort($unusedImports, fn($a, $b) => $b['line'] <=> $a['line']);
        
        foreach ($unusedImports as $import) {
            $lineIndex = $import['line'] - 1; // Convert to 0-based index
            
            if (isset($lines[$lineIndex])) {
                // Check if this line contains the use statement
                $line = $lines[$lineIndex];
                if (strpos($line, 'use ') !== false && strpos($line, $import['name']) !== false) {
                    // Remove the line
                    unset($lines[$lineIndex]);
                }
            }
        }
        
        // Reindex array and join back
        $lines = array_values($lines);
        $newContent = implode("\n", $lines);
        
        // Create backup before modifying
        $backupPath = $filePath . '.backup.' . time();
        copy($filePath, $backupPath);
        
        $result = file_put_contents($filePath, $newContent);
        
        if ($result === false) {
            // Restore backup if write failed
            copy($backupPath, $filePath);
            unlink($backupPath);
            return false;
        }
        
        // Remove backup on success
        unlink($backupPath);
        return true;
    }

    public function findUnusedMethods(PhpFileAnalysis $analysis): array
    {
        $unusedMethods = [];
        $usedMethods = $this->extractUsedMethods($analysis);
        
        foreach ($analysis->methods as $method) {
            // Only check private and protected methods for unused detection
            // Public methods might be used externally and should not be automatically removed
            if (in_array($method['visibility'], ['private', 'protected'])) {
                $methodKey = $this->getMethodKey($method['class'], $method['name']);
                
                if (!$this->isMethodUsed($methodKey, $usedMethods)) {
                    $unusedMethods[] = [
                        'name' => $method['name'],
                        'class' => $method['class'],
                        'line' => $method['line'],
                        'visibility' => $method['visibility'],
                        'static' => $method['static'],
                        'reason' => 'Method not called within the file'
                    ];
                }
            }
        }
        
        return $unusedMethods;
    }

    /**
     * Find unused methods across multiple files with class hierarchy analysis
     */
    public function findUnusedMethodsAcrossFiles(array $analyses): array
    {
        $hierarchyAnalyzer = new ClassHierarchyAnalyzer();
        $hierarchyAnalyzer->buildHierarchy($analyses);
        
        $allMethodCalls = $hierarchyAnalyzer->findMethodUsage();
        $unusedMethods = [];
        
        foreach ($analyses as $analysis) {
            foreach ($analysis->methods as $method) {
                // Only check private and protected methods
                if (in_array($method['visibility'], ['private', 'protected'])) {
                    $className = $this->getFullClassName($method['class'], $analysis->namespace);
                    
                    if ($className && !$hierarchyAnalyzer->isMethodUsed($className, $method['name'], $allMethodCalls)) {
                        $unusedMethods[] = [
                            'name' => $method['name'],
                            'class' => $method['class'],
                            'fullClass' => $className,
                            'line' => $method['line'],
                            'visibility' => $method['visibility'],
                            'static' => $method['static'],
                            'filePath' => $analysis->filePath,
                            'reason' => 'Method not called anywhere in the codebase'
                        ];
                    }
                }
            }
        }
        
        return $unusedMethods;
    }

    /**
     * Get full class name including namespace
     */
    private function getFullClassName(?string $className, ?string $namespace): ?string
    {
        if (!$className) {
            return null;
        }

        if (strpos($className, '\\') !== false) {
            return $className; // Already fully qualified
        }

        return $namespace ? "{$namespace}\\{$className}" : $className;
    }

    public function findDuplicateMethods(array $analyses): array
    {
        $duplicateDetector = new DuplicateDetector();
        return $duplicateDetector->findDuplicateMethods($analyses);
    }
}

class ClassUsageVisitor extends NodeVisitorAbstract
{
    private array $usedClasses = [];

    public function enterNode(Node $node)
    {
        // Track class instantiations (new ClassName)
        if ($node instanceof Node\Expr\New_) {
            if ($node->class instanceof Node\Name) {
                $this->usedClasses[] = $node->class->toString();
            }
        }
        // Track static method calls (ClassName::method)
        elseif ($node instanceof Node\Expr\StaticCall) {
            if ($node->class instanceof Node\Name) {
                $this->usedClasses[] = $node->class->toString();
            }
        }
        // Track class constant access (ClassName::CONSTANT)
        elseif ($node instanceof Node\Expr\ClassConstFetch) {
            if ($node->class instanceof Node\Name) {
                $this->usedClasses[] = $node->class->toString();
            }
        }
        // Track instanceof checks
        elseif ($node instanceof Node\Expr\Instanceof_) {
            if ($node->class instanceof Node\Name) {
                $this->usedClasses[] = $node->class->toString();
            }
        }
        // Track type hints in method parameters
        elseif ($node instanceof Node\Param) {
            if ($node->type instanceof Node\Name) {
                $this->usedClasses[] = $node->type->toString();
            } elseif ($node->type instanceof Node\UnionType) {
                foreach ($node->type->types as $type) {
                    if ($type instanceof Node\Name) {
                        $this->usedClasses[] = $type->toString();
                    }
                }
            }
        }
        // Track function and method return types
        elseif ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) {
            if ($node->returnType instanceof Node\Name) {
                $this->usedClasses[] = $node->returnType->toString();
            } elseif ($node->returnType instanceof Node\UnionType) {
                foreach ($node->returnType->types as $type) {
                    if ($type instanceof Node\Name) {
                        $this->usedClasses[] = $type->toString();
                    }
                }
            }
        }
        // Track catch blocks
        elseif ($node instanceof Node\Stmt\Catch_) {
            foreach ($node->types as $type) {
                if ($type instanceof Node\Name) {
                    $this->usedClasses[] = $type->toString();
                }
            }
        }
        // Track property types
        elseif ($node instanceof Node\Stmt\Property) {
            if ($node->type instanceof Node\Name) {
                $this->usedClasses[] = $node->type->toString();
            } elseif ($node->type instanceof Node\UnionType) {
                foreach ($node->type->types as $type) {
                    if ($type instanceof Node\Name) {
                        $this->usedClasses[] = $type->toString();
                    }
                }
            }
        }
        // Track trait usage
        elseif ($node instanceof Node\Stmt\TraitUse) {
            foreach ($node->traits as $trait) {
                if ($trait instanceof Node\Name) {
                    $this->usedClasses[] = $trait->toString();
                }
            }
        }
    }

    public function getUsedClasses(): array
    {
        return array_unique($this->usedClasses);
    }
}

class MethodUsageVisitor extends NodeVisitorAbstract
{
    private array $usedMethods = [];
    private ?string $currentClass = null;

    public function enterNode(Node $node)
    {
        // Track current class context
        if ($node instanceof Node\Stmt\Class_) {
            $this->currentClass = $node->name?->toString();
        }
        // Track method calls ($this->method(), $object->method())
        elseif ($node instanceof Node\Expr\MethodCall) {
            if ($node->name instanceof Node\Identifier) {
                $methodName = $node->name->toString();
                
                // If it's $this->method(), track it as current class method
                if ($node->var instanceof Node\Expr\Variable && 
                    $node->var->name === 'this' && 
                    $this->currentClass) {
                    $this->usedMethods[] = "{$this->currentClass}::{$methodName}";
                } else {
                    // For other object method calls, we can't determine the exact class
                    // but we track the method name for potential matches
                    $this->usedMethods[] = $methodName;
                }
            }
        }
        // Track static method calls (ClassName::method(), self::method(), static::method())
        elseif ($node instanceof Node\Expr\StaticCall) {
            if ($node->name instanceof Node\Identifier) {
                $methodName = $node->name->toString();
                
                if ($node->class instanceof Node\Name) {
                    $className = $node->class->toString();
                    
                    // Handle self and static references
                    if (in_array($className, ['self', 'static']) && $this->currentClass) {
                        $this->usedMethods[] = "{$this->currentClass}::{$methodName}";
                    } else {
                        $this->usedMethods[] = "{$className}::{$methodName}";
                    }
                }
            }
        }
        // Track function calls
        elseif ($node instanceof Node\Expr\FuncCall) {
            if ($node->name instanceof Node\Name) {
                $this->usedMethods[] = $node->name->toString();
            }
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->currentClass = null;
        }
    }

    public function getUsedMethods(): array
    {
        return array_unique($this->usedMethods);
    }
}

class VariableUsageVisitor extends NodeVisitorAbstract
{
    private array $scopes = [];
    private array $unusedVariables = [];
    private ?string $currentClass = null;
    private ?string $currentMethod = null;
    private array $assignmentTargets = [];
    private array $globalVariables = ['_GET', '_POST', '_SESSION', '_COOKIE', '_SERVER', '_ENV', '_FILES', 'GLOBALS'];

    public function enterNode(Node $node)
    {
        // Track class context
        if ($node instanceof Node\Stmt\Class_) {
            $this->currentClass = $node->name?->toString();
        }
        // Track method/function context and create new scope
        elseif ($node instanceof Node\Stmt\ClassMethod) {
            $this->currentMethod = $node->name->toString();
            $this->enterScope();
            
            // Add method parameters to current scope as used variables (parameters are always considered used)
            foreach ($node->params as $param) {
                if ($param->var instanceof Node\Expr\Variable && is_string($param->var->name)) {
                    $this->declareVariable($param->var->name, $param->getStartLine());
                    $this->markVariableAsUsed($param->var->name);
                }
            }
        }
        elseif ($node instanceof Node\Stmt\Function_) {
            $this->currentMethod = $node->name->toString();
            $this->enterScope();
            
            // Add function parameters to current scope as used variables
            foreach ($node->params as $param) {
                if ($param->var instanceof Node\Expr\Variable && is_string($param->var->name)) {
                    $this->declareVariable($param->var->name, $param->getStartLine());
                    $this->markVariableAsUsed($param->var->name);
                }
            }
        }
        // Track closures and anonymous functions
        elseif ($node instanceof Node\Expr\Closure) {
            $this->enterScope();
            
            // Add closure parameters
            foreach ($node->params as $param) {
                if ($param->var instanceof Node\Expr\Variable && is_string($param->var->name)) {
                    $this->declareVariable($param->var->name, $param->getStartLine());
                    $this->markVariableAsUsed($param->var->name);
                }
            }
            
            // Add use variables
            foreach ($node->uses as $use) {
                if ($use->var instanceof Node\Expr\Variable && is_string($use->var->name)) {
                    $this->markVariableAsUsed($use->var->name);
                }
            }
        }
        // Track variable assignments (declarations)
        elseif ($node instanceof Node\Expr\Assign) {
            if ($node->var instanceof Node\Expr\Variable && is_string($node->var->name)) {
                $this->declareVariable($node->var->name, $node->getStartLine());
                // Track this as an assignment target so we don't mark it as used
                $this->assignmentTargets[] = $node->var->name;
            }
            // Handle array assignments like $arr[] = value or $arr[$key] = value
            elseif ($node->var instanceof Node\Expr\ArrayDimFetch) {
                $this->checkForVariableUsage($node->var);
            }
            // Handle property assignments like $this->prop = value
            elseif ($node->var instanceof Node\Expr\PropertyFetch) {
                $this->checkForVariableUsage($node->var);
            }
            
            // Also check the right side for variable usage
            $this->checkForVariableUsage($node->expr);
        }
        // Track compound assignments (+=, -=, etc.)
        elseif ($node instanceof Node\Expr\AssignOp) {
            $this->checkForVariableUsage($node->var);
            $this->checkForVariableUsage($node->expr);
        }
        // Track pre/post increment/decrement
        elseif ($node instanceof Node\Expr\PreInc || $node instanceof Node\Expr\PostInc ||
                 $node instanceof Node\Expr\PreDec || $node instanceof Node\Expr\PostDec) {
            $this->checkForVariableUsage($node->var);
        }
        // Track variable usage in other contexts (but not if it's an assignment target)
        elseif ($node instanceof Node\Expr\Variable) {
            if (is_string($node->name) && 
                $node->name !== 'this' && 
                !in_array($node->name, $this->assignmentTargets) &&
                !in_array($node->name, $this->globalVariables)) {
                $this->markVariableAsUsed($node->name);
            }
        }
        // Track foreach variables
        elseif ($node instanceof Node\Stmt\Foreach_) {
            // First check the iterable expression for variable usage
            $this->checkForVariableUsage($node->expr);
            
            // Then declare and mark foreach variables as used
            if ($node->keyVar instanceof Node\Expr\Variable && is_string($node->keyVar->name)) {
                $this->declareVariable($node->keyVar->name, $node->getStartLine());
                $this->markVariableAsUsed($node->keyVar->name);
            }
            if ($node->valueVar instanceof Node\Expr\Variable && is_string($node->valueVar->name)) {
                $this->declareVariable($node->valueVar->name, $node->getStartLine());
                $this->markVariableAsUsed($node->valueVar->name);
            }
        }
        // Track catch variables
        elseif ($node instanceof Node\Stmt\Catch_) {
            if ($node->var instanceof Node\Expr\Variable && is_string($node->var->name)) {
                $this->declareVariable($node->var->name, $node->getStartLine());
                $this->markVariableAsUsed($node->var->name);
            }
        }
        // Track return statements
        elseif ($node instanceof Node\Stmt\Return_) {
            if ($node->expr) {
                $this->checkForVariableUsage($node->expr);
            }
        }
        // Track echo statements
        elseif ($node instanceof Node\Stmt\Echo_) {
            foreach ($node->exprs as $expr) {
                $this->checkForVariableUsage($expr);
            }
        }
        // Track method calls
        elseif ($node instanceof Node\Expr\MethodCall) {
            $this->checkForVariableUsage($node->var);
            foreach ($node->args as $arg) {
                $this->checkForVariableUsage($arg->value);
            }
        }
        // Track static method calls
        elseif ($node instanceof Node\Expr\StaticCall) {
            foreach ($node->args as $arg) {
                $this->checkForVariableUsage($arg->value);
            }
        }
        // Track function calls
        elseif ($node instanceof Node\Expr\FuncCall) {
            foreach ($node->args as $arg) {
                $this->checkForVariableUsage($arg->value);
            }
        }
        // Track if/elseif conditions
        elseif ($node instanceof Node\Stmt\If_) {
            $this->checkForVariableUsage($node->cond);
        }
        elseif ($node instanceof Node\Stmt\ElseIf_) {
            $this->checkForVariableUsage($node->cond);
        }
        // Track while/do-while conditions
        elseif ($node instanceof Node\Stmt\While_) {
            $this->checkForVariableUsage($node->cond);
        }
        elseif ($node instanceof Node\Stmt\Do_) {
            $this->checkForVariableUsage($node->cond);
        }
        // Track for loop conditions
        elseif ($node instanceof Node\Stmt\For_) {
            foreach ($node->init as $init) {
                $this->checkForVariableUsage($init);
            }
            foreach ($node->cond as $cond) {
                $this->checkForVariableUsage($cond);
            }
            foreach ($node->loop as $loop) {
                $this->checkForVariableUsage($loop);
            }
        }
        // Track switch conditions
        elseif ($node instanceof Node\Stmt\Switch_) {
            $this->checkForVariableUsage($node->cond);
        }
        // Track case conditions
        elseif ($node instanceof Node\Stmt\Case_) {
            if ($node->cond) {
                $this->checkForVariableUsage($node->cond);
            }
        }
        // Track throw statements
        elseif ($node instanceof Node\Stmt\Throw_) {
            $this->checkForVariableUsage($node->expr);
        }
        // Track global statements
        elseif ($node instanceof Node\Stmt\Global_) {
            foreach ($node->vars as $var) {
                if ($var instanceof Node\Expr\Variable && is_string($var->name)) {
                    $this->markVariableAsUsed($var->name);
                }
            }
        }
        // Track static variables
        elseif ($node instanceof Node\Stmt\Static_) {
            foreach ($node->vars as $var) {
                if ($var->var instanceof Node\Expr\Variable && is_string($var->var->name)) {
                    $this->declareVariable($var->var->name, $node->getStartLine());
                    $this->markVariableAsUsed($var->var->name);
                }
            }
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
            $this->collectUnusedVariables();
            $this->exitScope();
            $this->currentMethod = null;
        }
        elseif ($node instanceof Node\Expr\Closure) {
            $this->collectUnusedVariables();
            $this->exitScope();
        }
        elseif ($node instanceof Node\Stmt\Class_) {
            $this->currentClass = null;
        }
        elseif ($node instanceof Node\Expr\Assign) {
            // Clear assignment targets when leaving assignment
            if ($node->var instanceof Node\Expr\Variable && is_string($node->var->name)) {
                $key = array_search($node->var->name, $this->assignmentTargets);
                if ($key !== false) {
                    unset($this->assignmentTargets[$key]);
                }
            }
        }
    }

    private function checkForVariableUsage(Node $node): void
    {
        if ($node instanceof Node\Expr\Variable && is_string($node->name) && 
            $node->name !== 'this' && !in_array($node->name, $this->globalVariables)) {
            $this->markVariableAsUsed($node->name);
        } 
        // Binary operations
        elseif ($node instanceof Node\Expr\BinaryOp) {
            $this->checkForVariableUsage($node->left);
            $this->checkForVariableUsage($node->right);
        } 
        // String concatenation
        elseif ($node instanceof Node\Expr\Concat) {
            $this->checkForVariableUsage($node->left);
            $this->checkForVariableUsage($node->right);
        } 
        // Method calls
        elseif ($node instanceof Node\Expr\MethodCall) {
            $this->checkForVariableUsage($node->var);
            foreach ($node->args as $arg) {
                $this->checkForVariableUsage($arg->value);
            }
        } 
        // Static method calls
        elseif ($node instanceof Node\Expr\StaticCall) {
            foreach ($node->args as $arg) {
                $this->checkForVariableUsage($arg->value);
            }
        }
        // Function calls
        elseif ($node instanceof Node\Expr\FuncCall) {
            foreach ($node->args as $arg) {
                $this->checkForVariableUsage($arg->value);
            }
        } 
        // Property access
        elseif ($node instanceof Node\Expr\PropertyFetch) {
            $this->checkForVariableUsage($node->var);
        } 
        // Array access
        elseif ($node instanceof Node\Expr\ArrayDimFetch) {
            $this->checkForVariableUsage($node->var);
            if ($node->dim) {
                $this->checkForVariableUsage($node->dim);
            }
        }
        // Array creation
        elseif ($node instanceof Node\Expr\Array_) {
            foreach ($node->items as $item) {
                if ($item) {
                    if ($item->key) {
                        $this->checkForVariableUsage($item->key);
                    }
                    $this->checkForVariableUsage($item->value);
                }
            }
        }
        // Ternary operator
        elseif ($node instanceof Node\Expr\Ternary) {
            $this->checkForVariableUsage($node->cond);
            if ($node->if) {
                $this->checkForVariableUsage($node->if);
            }
            $this->checkForVariableUsage($node->else);
        }
        // Null coalescing
        elseif ($node instanceof Node\Expr\BinaryOp\Coalesce) {
            $this->checkForVariableUsage($node->left);
            $this->checkForVariableUsage($node->right);
        }
        // Unary operations
        elseif ($node instanceof Node\Expr\UnaryMinus || $node instanceof Node\Expr\UnaryPlus ||
                 $node instanceof Node\Expr\BooleanNot || $node instanceof Node\Expr\BitwiseNot) {
            $this->checkForVariableUsage($node->expr);
        }
        // Cast operations
        elseif ($node instanceof Node\Expr\Cast) {
            $this->checkForVariableUsage($node->expr);
        }
        // Clone operation
        elseif ($node instanceof Node\Expr\Clone_) {
            $this->checkForVariableUsage($node->expr);
        }
        // Empty/isset checks
        elseif ($node instanceof Node\Expr\Empty_ || $node instanceof Node\Expr\Isset_) {
            foreach ($node->vars as $var) {
                $this->checkForVariableUsage($var);
            }
        }
        // Include/require statements
        elseif ($node instanceof Node\Expr\Include_) {
            $this->checkForVariableUsage($node->expr);
        }
        // Instanceof checks
        elseif ($node instanceof Node\Expr\Instanceof_) {
            $this->checkForVariableUsage($node->expr);
        }
        // New object creation
        elseif ($node instanceof Node\Expr\New_) {
            foreach ($node->args as $arg) {
                $this->checkForVariableUsage($arg->value);
            }
        }
        // Print statements
        elseif ($node instanceof Node\Expr\Print_) {
            $this->checkForVariableUsage($node->expr);
        }
        // Closures
        elseif ($node instanceof Node\Expr\Closure) {
            // Variables used in closures are handled in enterNode
            foreach ($node->uses as $use) {
                if ($use->var instanceof Node\Expr\Variable && is_string($use->var->name)) {
                    $this->markVariableAsUsed($use->var->name);
                }
            }
        }
        // Exit/die statements
        elseif ($node instanceof Node\Expr\Exit_) {
            if ($node->expr) {
                $this->checkForVariableUsage($node->expr);
            }
        }
        // Yield expressions
        elseif ($node instanceof Node\Expr\Yield_) {
            if ($node->key) {
                $this->checkForVariableUsage($node->key);
            }
            if ($node->value) {
                $this->checkForVariableUsage($node->value);
            }
        }
        // Yield from expressions
        elseif ($node instanceof Node\Expr\YieldFrom) {
            $this->checkForVariableUsage($node->expr);
        }
    }

    private function enterScope(): void
    {
        $this->scopes[] = [
            'declared' => [],
            'used' => []
        ];
    }

    private function exitScope(): void
    {
        array_pop($this->scopes);
    }

    private function getCurrentScope(): ?array
    {
        return end($this->scopes) ?: null;
    }

    private function declareVariable(string $name, int $line): void
    {
        if (empty($this->scopes)) {
            return;
        }
        
        $scope = &$this->scopes[count($this->scopes) - 1];
        if (!isset($scope['declared'][$name])) {
            $scope['declared'][$name] = $line;
        }
    }

    private function markVariableAsUsed(string $name): void
    {
        if (empty($this->scopes)) {
            return;
        }
        
        // Mark variable as used in current scope
        $scope = &$this->scopes[count($this->scopes) - 1];
        $scope['used'][$name] = true;
    }

    private function collectUnusedVariables(): void
    {
        $scope = $this->getCurrentScope();
        if (!$scope) {
            return;
        }

        foreach ($scope['declared'] as $varName => $line) {
            if (!isset($scope['used'][$varName])) {
                $this->unusedVariables[] = [
                    'name' => $varName,
                    'line' => $line,
                    'class' => $this->currentClass,
                    'method' => $this->currentMethod,
                    'reason' => 'Variable declared but never used'
                ];
            }
        }
    }

    public function getUnusedVariables(): array
    {
        return $this->unusedVariables;
    }
}

class PhpAnalysisVisitor extends NodeVisitorAbstract
{
    private array $classes = [];
    private array $methods = [];
    private array $functions = [];
    private array $useStatements = [];
    private array $variables = [];
    private array $constants = [];
    private array $dependencies = [];
    private ?string $namespace = null;
    private ?string $currentClass = null;

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->namespace = $node->name?->toString();
        } elseif ($node instanceof Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $this->useStatements[] = [
                    'name' => $this->resolveNodeName($use->name),
                    'alias' => $use->alias?->toString(),
                    'line' => $node->getStartLine(),
                ];
                $this->dependencies[] = $this->resolveNodeName($use->name);
            }
        } elseif ($node instanceof Node\Stmt\Class_) {
            $className = $node->name?->toString() ?? 'anonymous';
            $this->currentClass = $className;
            $this->classes[] = [
                'name' => $className,
                'line' => $node->getStartLine(),
                'extends' => $node->extends ? $this->resolveNodeName($node->extends) : null,
                'implements' => array_map(fn($impl) => $this->resolveNodeName($impl), $node->implements),
                'abstract' => $node->isAbstract(),
                'final' => $node->isFinal(),
            ];
            
            if ($node->extends) {
                $this->dependencies[] = $this->resolveNodeName($node->extends);
            }
            foreach ($node->implements as $interface) {
                $this->dependencies[] = $this->resolveNodeName($interface);
            }
        } elseif ($node instanceof Node\Stmt\Interface_) {
            $interfaceName = $node->name?->toString() ?? 'anonymous';
            $this->currentClass = $interfaceName;
            $this->classes[] = [
                'name' => $interfaceName,
                'line' => $node->getStartLine(),
                'extends' => null,
                'implements' => array_map(fn($impl) => $impl->toString(), $node->extends),
                'abstract' => false,
                'final' => false,
                'type' => 'interface',
            ];
            
            foreach ($node->extends as $interface) {
                $this->dependencies[] = $interface->toString();
            }
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            $methodName = $node->name->toString();
            $this->methods[] = [
                'name' => $methodName,
                'class' => $this->currentClass,
                'line' => $node->getStartLine(),
                'visibility' => $this->getVisibility($node),
                'static' => $node->isStatic(),
                'abstract' => $node->isAbstract(),
                'final' => $node->isFinal(),
                'parameters' => $this->extractParameters($node->params),
            ];
        } elseif ($node instanceof Node\Stmt\Function_) {
            $this->functions[] = [
                'name' => $node->name->toString(),
                'line' => $node->getStartLine(),
                'parameters' => $this->extractParameters($node->params),
            ];
        } elseif ($node instanceof Node\Expr\Variable) {
            if (is_string($node->name)) {
                $this->variables[] = [
                    'name' => $node->name,
                    'line' => $node->getStartLine(),
                    'class' => $this->currentClass,
                ];
            }
        } elseif ($node instanceof Node\Stmt\ClassConst) {
            foreach ($node->consts as $const) {
                $this->constants[] = [
                    'name' => $const->name->toString(),
                    'class' => $this->currentClass,
                    'line' => $node->getStartLine(),
                    'visibility' => $this->getVisibility($node),
                ];
            }
        } elseif ($node instanceof Node\Stmt\Const_) {
            foreach ($node->consts as $const) {
                $this->constants[] = [
                    'name' => $const->name->toString(),
                    'class' => null,
                    'line' => $node->getStartLine(),
                    'visibility' => 'public',
                ];
            }
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Interface_) {
            $this->currentClass = null;
        }
    }

    private function getVisibility(Node $node): string
    {
        if (method_exists($node, 'isPrivate') && $node->isPrivate()) {
            return 'private';
        } elseif (method_exists($node, 'isProtected') && $node->isProtected()) {
            return 'protected';
        }
        return 'public';
    }

    private function extractParameters(array $params): array
    {
        return array_map(fn($param) => [
            'name' => $param->var->name,
            'type' => $this->getParameterType($param->type),
            'default' => $param->default !== null,
        ], $params);
    }
    
    private function getParameterType($type): ?string
    {
        if ($type === null) {
            return null;
        }
        
        if (method_exists($type, 'toString')) {
            return $type->toString();
        }
        
        if ($type instanceof \PhpParser\Node\Name) {
            return $type->toString();
        }
        
        if ($type instanceof \PhpParser\Node\Identifier) {
            return $type->name;
        }
        
        if ($type instanceof \PhpParser\Node\NullableType) {
            return '?' . $this->getParameterType($type->type);
        }
        
        if ($type instanceof \PhpParser\Node\UnionType) {
            $types = array_map([$this, 'getParameterType'], $type->types);
            return implode('|', $types);
        }
        
        return 'mixed';
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function getUseStatements(): array
    {
        return $this->useStatements;
    }

    public function getVariables(): array
    {
        return array_unique($this->variables, SORT_REGULAR);
    }

    public function getConstants(): array
    {
        return $this->constants;
    }

    public function getDependencies(): array
    {
        return array_unique($this->dependencies);
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    private function resolveNodeName(Node $node): string
    {
        if ($node instanceof Node\Name) {
            return $node->toString();
        }
        
        return (string) $node;
    }
}