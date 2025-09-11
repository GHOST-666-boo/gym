<?php

namespace App\Services\Cleanup;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class PhpAnalysisVisitor extends NodeVisitorAbstract
{
    private ?string $namespace = null;
    private array $useStatements = [];
    private array $classes = [];
    private array $methods = [];
    private array $functions = [];
    private array $variables = [];
    private array $constants = [];
    private array $dependencies = [];
    private ?string $currentClass = null;

    public function enterNode(Node $node)
    {
        // Track namespace
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->namespace = $node->name?->toString();
        }
        // Track use statements
        elseif ($node instanceof Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $name = $use->name->toString();
                $alias = $use->alias?->toString();
                $this->useStatements[] = [
                    'name' => $name,
                    'alias' => $alias,
                    'line' => $node->getStartLine()
                ];
                $this->dependencies[] = $name;
            }
        }
        // Track classes
        elseif ($node instanceof Node\Stmt\Class_) {
            $className = $node->name?->toString();
            $this->currentClass = $className;
            
            $extends = $node->extends?->toString();
            $implements = array_map(fn($interface) => $interface->toString(), $node->implements);
            
            $this->classes[] = [
                'name' => $className,
                'extends' => $extends,
                'implements' => $implements,
                'line' => $node->getStartLine()
            ];
            
            if ($extends) {
                $this->dependencies[] = $extends;
            }
            foreach ($implements as $interface) {
                $this->dependencies[] = $interface;
            }
        }
        // Track interfaces
        elseif ($node instanceof Node\Stmt\Interface_) {
            $interfaceName = $node->name?->toString();
            $this->currentClass = $interfaceName;
            
            $extends = array_map(fn($parent) => $parent->toString(), $node->extends);
            
            $this->classes[] = [
                'name' => $interfaceName,
                'extends' => null,
                'implements' => $extends,
                'line' => $node->getStartLine(),
                'type' => 'interface'
            ];
            
            foreach ($extends as $parent) {
                $this->dependencies[] = $parent;
            }
        }
        // Track traits
        elseif ($node instanceof Node\Stmt\Trait_) {
            $traitName = $node->name?->toString();
            $this->currentClass = $traitName;
            
            $this->classes[] = [
                'name' => $traitName,
                'extends' => null,
                'implements' => [],
                'line' => $node->getStartLine(),
                'type' => 'trait'
            ];
        }
        // Track methods
        elseif ($node instanceof Node\Stmt\ClassMethod) {
            $methodName = $node->name->toString();
            $visibility = 'public'; // default
            
            if ($node->isPrivate()) {
                $visibility = 'private';
            } elseif ($node->isProtected()) {
                $visibility = 'protected';
            }
            
            $this->methods[] = [
                'name' => $methodName,
                'class' => $this->currentClass,
                'visibility' => $visibility,
                'static' => $node->isStatic(),
                'line' => $node->getStartLine(),
                'parameters' => $this->extractParameters($node->params)
            ];
        }
        // Track functions
        elseif ($node instanceof Node\Stmt\Function_) {
            $functionName = $node->name->toString();
            
            $this->functions[] = [
                'name' => $functionName,
                'line' => $node->getStartLine(),
                'parameters' => $this->extractParameters($node->params)
            ];
        }
        // Track constants
        elseif ($node instanceof Node\Stmt\ClassConst) {
            foreach ($node->consts as $const) {
                $this->constants[] = [
                    'name' => $const->name->toString(),
                    'class' => $this->currentClass,
                    'line' => $node->getStartLine()
                ];
            }
        }
        // Track global constants
        elseif ($node instanceof Node\Stmt\Const_) {
            foreach ($node->consts as $const) {
                $this->constants[] = [
                    'name' => $const->name->toString(),
                    'class' => null,
                    'line' => $node->getStartLine()
                ];
            }
        }
        // Track variables (basic tracking for now)
        elseif ($node instanceof Node\Expr\Variable && is_string($node->name)) {
            $this->variables[] = [
                'name' => $node->name,
                'line' => $node->getStartLine(),
                'class' => $this->currentClass
            ];
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_ || 
            $node instanceof Node\Stmt\Interface_ || 
            $node instanceof Node\Stmt\Trait_) {
            $this->currentClass = null;
        }
    }

    private function extractParameters(array $params): array
    {
        $parameters = [];
        
        foreach ($params as $param) {
            $type = null;
            if ($param->type instanceof Node\Name) {
                $type = $param->type->toString();
            } elseif ($param->type instanceof Node\Identifier) {
                $type = $param->type->toString();
            }
            
            $parameters[] = [
                'name' => $param->var->name,
                'type' => $type,
                'hasDefault' => $param->default !== null
            ];
        }
        
        return $parameters;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function getUseStatements(): array
    {
        return $this->useStatements;
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
}