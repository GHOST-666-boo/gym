<?php

namespace App\Services\Cleanup;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class MethodCallExtractor extends NodeVisitorAbstract
{
    private array $methodCalls = [];
    private ?string $currentNamespace;
    private array $useStatements;
    private ?string $currentClass = null;

    public function __construct(?string $namespace, array $useStatements)
    {
        $this->currentNamespace = $namespace;
        $this->useStatements = $useStatements;
    }

    public function enterNode(Node $node)
    {
        // Track current class context
        if ($node instanceof Node\Stmt\Class_) {
            $this->currentClass = $this->getFullClassName($node->name?->toString());
        }
        // Track method calls ($this->method(), $object->method())
        elseif ($node instanceof Node\Expr\MethodCall) {
            if ($node->name instanceof Node\Identifier) {
                $methodName = $node->name->toString();
                
                // Handle $this->method() calls
                if ($node->var instanceof Node\Expr\Variable && 
                    $node->var->name === 'this' && 
                    $this->currentClass) {
                    $this->methodCalls[] = "{$this->currentClass}::{$methodName}";
                }
                // Handle $object->method() calls - we can't always determine the exact class
                // but we track the method name for potential matches
                else {
                    $this->methodCalls[] = "unknown::{$methodName}";
                }
            }
        }
        // Track static method calls (ClassName::method(), self::method(), static::method(), parent::method())
        elseif ($node instanceof Node\Expr\StaticCall) {
            if ($node->name instanceof Node\Identifier) {
                $methodName = $node->name->toString();
                
                if ($node->class instanceof Node\Name) {
                    $className = $node->class->toString();
                    
                    // Handle self, static, and parent references
                    if ($className === 'self' || $className === 'static') {
                        if ($this->currentClass) {
                            $this->methodCalls[] = "{$this->currentClass}::{$methodName}";
                        }
                    } elseif ($className === 'parent') {
                        // For parent calls, we'd need hierarchy info to resolve properly
                        // For now, just mark it as a parent call
                        $this->methodCalls[] = "parent::{$methodName}";
                    } else {
                        // Resolve the class name using use statements
                        $resolvedClassName = $this->resolveClassName($className);
                        $this->methodCalls[] = "{$resolvedClassName}::{$methodName}";
                    }
                }
            }
        }
        // Track function calls
        elseif ($node instanceof Node\Expr\FuncCall) {
            if ($node->name instanceof Node\Name) {
                $functionName = $node->name->toString();
                $this->methodCalls[] = "function::{$functionName}";
            }
        }
        // Track constructor calls (new ClassName())
        elseif ($node instanceof Node\Expr\New_) {
            if ($node->class instanceof Node\Name) {
                $className = $this->resolveClassName($node->class->toString());
                $this->methodCalls[] = "{$className}::__construct";
            }
        }
        // Track method calls in array callbacks
        elseif ($node instanceof Node\Expr\Array_) {
            foreach ($node->items as $item) {
                if ($item && $item->value instanceof Node\Expr\Array_ && count($item->value->items) === 2) {
                    $firstItem = $item->value->items[0];
                    $secondItem = $item->value->items[1];
                    
                    if ($firstItem && $secondItem && 
                        $secondItem->value instanceof Node\Scalar\String_) {
                        $methodName = $secondItem->value->value;
                        
                        // Handle [$object, 'method'] or [ClassName::class, 'method']
                        if ($firstItem->value instanceof Node\Expr\Variable) {
                            $this->methodCalls[] = "unknown::{$methodName}";
                        } elseif ($firstItem->value instanceof Node\Expr\ClassConstFetch) {
                            if ($firstItem->value->class instanceof Node\Name) {
                                $className = $this->resolveClassName($firstItem->value->class->toString());
                                $this->methodCalls[] = "{$className}::{$methodName}";
                            }
                        }
                    }
                }
            }
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->currentClass = null;
        }
    }

    /**
     * Get all method calls found
     */
    public function getMethodCalls(): array
    {
        return array_unique($this->methodCalls);
    }

    /**
     * Resolve class name using namespace and use statements
     */
    private function resolveClassName(string $className): string
    {
        // If already fully qualified, return as is
        if (strpos($className, '\\') === 0) {
            return substr($className, 1);
        }

        // Check use statements for alias resolution
        foreach ($this->useStatements as $use) {
            $alias = $use['alias'] ?? $this->getShortClassName($use['name']);
            if ($alias === $className) {
                return $use['name'];
            }
        }

        // If not found in use statements, assume it's in the same namespace
        return $this->currentNamespace ? "{$this->currentNamespace}\\{$className}" : $className;
    }

    /**
     * Get full class name including namespace
     */
    private function getFullClassName(?string $className): ?string
    {
        if (!$className) {
            return null;
        }

        if (strpos($className, '\\') !== false) {
            return $className; // Already fully qualified
        }

        return $this->currentNamespace ? "{$this->currentNamespace}\\{$className}" : $className;
    }

    /**
     * Get short class name from fully qualified name
     */
    private function getShortClassName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }
}