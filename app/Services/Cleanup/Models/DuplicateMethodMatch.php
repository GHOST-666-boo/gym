<?php

namespace App\Services\Cleanup\Models;

class DuplicateMethodMatch
{
    public function __construct(
        public readonly array $method1,
        public readonly array $method2,
        public readonly float $similarity,
        public readonly RefactoringSuggestion $suggestion
    ) {}

    public function toArray(): array
    {
        return [
            'method1' => $this->method1,
            'method2' => $this->method2,
            'similarity' => $this->similarity,
            'suggestion' => $this->suggestion->toArray(),
        ];
    }

    public function getSimilarityPercentage(): int
    {
        return (int) round($this->similarity * 100);
    }

    public function getMethod1Location(): string
    {
        return $this->method1['filePath'] . ':' . $this->method1['line'];
    }

    public function getMethod2Location(): string
    {
        return $this->method2['filePath'] . ':' . $this->method2['line'];
    }

    public function getMethod1FullName(): string
    {
        $class = $this->method1['class'] ? $this->method1['class'] . '::' : '';
        return $class . $this->method1['name'];
    }

    public function getMethod2FullName(): string
    {
        $class = $this->method2['class'] ? $this->method2['class'] . '::' : '';
        return $class . $this->method2['name'];
    }

    public function isExactDuplicate(): bool
    {
        return $this->suggestion->type === 'exact_duplicate';
    }

    public function isNearDuplicate(): bool
    {
        return $this->suggestion->type === 'near_duplicate';
    }

    public function isSimilarLogic(): bool
    {
        return $this->suggestion->type === 'similar_logic';
    }
}