<?php

namespace App\Services\Cleanup\Models;

class ControllerMethodAnalysis
{
    public string $class;
    public string $method;
    public string $visibility;
    public bool $isUsed = false;
    public array $routes = [];
    public int $lineNumber = 0;
    public string $filePath = '';
    
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function getFullMethodName(): string
    {
        return $this->class . '@' . $this->method;
    }
    
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }
    
    public function isProtected(): bool
    {
        return $this->visibility === 'protected';
    }
    
    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }
    
    public function addRoute(string $routeName): void
    {
        if (!in_array($routeName, $this->routes)) {
            $this->routes[] = $routeName;
            $this->isUsed = true;
        }
    }
}