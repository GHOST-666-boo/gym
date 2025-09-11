<?php

namespace App\Services\Cleanup\Models;

class DependencyGraph
{
    private array $nodes = [];
    private array $edges = [];
    
    public function addNode(string $nodeId, array $metadata = []): void
    {
        $this->nodes[$nodeId] = $metadata;
    }
    
    public function addEdge(string $fromNode, string $toNode, string $type = 'depends'): void
    {
        if (!isset($this->edges[$fromNode])) {
            $this->edges[$fromNode] = [];
        }
        
        $this->edges[$fromNode][] = [
            'to' => $toNode,
            'type' => $type
        ];
    }
    
    public function addDependency(string $fromFile, string $toFile): void
    {
        $this->addNode($fromFile);
        $this->addNode($toFile);
        $this->addEdge($fromFile, $toFile, 'imports');
    }
    
    public function getDependencies(string $nodeId): array
    {
        return $this->edges[$nodeId] ?? [];
    }
    
    public function hasDependencies(string $nodeId): bool
    {
        return !empty($this->edges[$nodeId]);
    }
    
    public function getNodes(): array
    {
        return $this->nodes;
    }
    
    public function getEdges(): array
    {
        return $this->edges;
    }
}