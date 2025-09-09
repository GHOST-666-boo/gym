<?php

namespace App\Services\Cleanup\Models;

class PhpFileAnalysis
{
    public string $filePath;
    public string $code;
    public array $ast;
    public ?string $namespace = null;
    public array $useStatements = [];
    public array $classes = [];
    public array $methods = [];
    public array $functions = [];
    public array $variables = [];
    public array $imports = []; // Alias for useStatements for compatibility
    public array $constants = [];
    public array $dependencies = [];

    public function __construct(string $filePath, string $code, array $ast)
    {
        $this->filePath = $filePath;
        $this->code = $code;
        $this->ast = $ast;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getAst(): array
    {
        return $this->ast;
    }

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function addUseStatement(string $import, int $line): void
    {
        $this->useStatements[$import] = $line;
        $this->imports[$import] = ['class' => $import, 'line' => $line]; // For compatibility
    }

    public function getUseStatements(): array
    {
        return $this->useStatements;
    }

    public function addClass(string $className, int $line): void
    {
        $this->classes[$className] = [
            'name' => $className,
            'line' => $line
        ];
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    public function addMethod(string $className, string $methodName, string $visibility, bool $isStatic, int $line): void
    {
        $this->methods[] = [
            'class' => $className,
            'name' => $methodName,
            'visibility' => $visibility,
            'isStatic' => $isStatic,
            'line' => $line
        ];
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getMethodsByClass(string $className): array
    {
        return array_filter($this->methods, function ($method) use ($className) {
            return $method['class'] === $className;
        });
    }

    public function addFunction(string $functionName, int $line): void
    {
        $this->functions[$functionName] = [
            'name' => $functionName,
            'line' => $line
        ];
    }

    public function getFunctions(): array
    {
        return $this->functions;
    }

    public function addVariable(string $variableName, int $line): void
    {
        if (!isset($this->variables[$variableName])) {
            $this->variables[$variableName] = [];
        }
        $this->variables[$variableName][] = $line;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function hasClass(string $className): bool
    {
        return isset($this->classes[$className]);
    }

    public function hasMethod(string $className, string $methodName): bool
    {
        foreach ($this->methods as $method) {
            if ($method['class'] === $className && $method['name'] === $methodName) {
                return true;
            }
        }
        return false;
    }

    public function hasFunction(string $functionName): bool
    {
        return isset($this->functions[$functionName]);
    }

    public function hasVariable(string $variableName): bool
    {
        return isset($this->variables[$variableName]);
    }

    public function getStats(): array
    {
        return [
            'file' => $this->filePath,
            'namespace' => $this->namespace,
            'useStatements' => count($this->useStatements),
            'classes' => count($this->classes),
            'methods' => count($this->methods),
            'functions' => count($this->functions),
            'variables' => count($this->variables),
            'linesOfCode' => substr_count($this->code, "\n") + 1
        ];
    }
}