<?php

namespace App\Models\Cleanup;

class PhpFileAnalysis
{
    public function __construct(
        public readonly string $filePath,
        public readonly array $classes = [],
        public readonly array $methods = [],
        public readonly array $functions = [],
        public readonly array $useStatements = [],
        public readonly array $variables = [],
        public readonly array $constants = [],
        public readonly array $dependencies = [],
        public readonly ?string $namespace = null,
        public readonly array $errors = []
    ) {}

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getClassName(): ?string
    {
        return $this->classes[0]['name'] ?? null;
    }

    public function getMethodNames(): array
    {
        return array_column($this->methods, 'name');
    }

    public function getFunctionNames(): array
    {
        return array_column($this->functions, 'name');
    }

    public function getUseStatementNames(): array
    {
        return array_column($this->useStatements, 'name');
    }

    public function getVariableNames(): array
    {
        return array_column($this->variables, 'name');
    }

    public function toArray(): array
    {
        return [
            'filePath' => $this->filePath,
            'classes' => $this->classes,
            'methods' => $this->methods,
            'functions' => $this->functions,
            'useStatements' => $this->useStatements,
            'variables' => $this->variables,
            'constants' => $this->constants,
            'dependencies' => $this->dependencies,
            'namespace' => $this->namespace,
            'errors' => $this->errors,
        ];
    }
}