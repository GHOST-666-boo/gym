<?php

namespace App\Services\Cleanup\Models;

class RouteAnalysis
{
    public string $name;
    public string $uri;
    public string $method;
    public string $controller;
    public string $action;
    public array $middleware = [];
    public bool $isUsed = false;
    
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function getFullAction(): string
    {
        return $this->controller . '@' . $this->action;
    }
    
    public function hasMiddleware(string $middleware): bool
    {
        return in_array($middleware, $this->middleware);
    }
}