<?php

namespace App\Services\Cleanup\Models;

class MaintenanceRecommendation
{
    public string $type;
    public string $priority;
    public string $title;
    public string $description;
    public array $actionItems;
    public string $estimatedEffort;
    public string $expectedBenefit;
    public array $tags;
    public \DateTime $createdAt;

    public function __construct(array $data = [])
    {
        $this->type = $data['type'] ?? '';
        $this->priority = $data['priority'] ?? 'medium';
        $this->title = $data['title'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->actionItems = $data['action_items'] ?? [];
        $this->estimatedEffort = $data['estimated_effort'] ?? '';
        $this->expectedBenefit = $data['expected_benefit'] ?? '';
        $this->tags = $data['tags'] ?? [];
        $this->createdAt = $data['created_at'] ?? new \DateTime();
    }

    /**
     * Get priority level as numeric value for sorting
     */
    public function getPriorityLevel(): int
    {
        return match ($this->priority) {
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        };
    }

    /**
     * Get formatted priority with color coding
     */
    public function getFormattedPriority(): array
    {
        return match ($this->priority) {
            'high' => ['text' => 'High Priority', 'color' => 'red', 'urgency' => 'Immediate attention required'],
            'medium' => ['text' => 'Medium Priority', 'color' => 'orange', 'urgency' => 'Should be addressed soon'],
            'low' => ['text' => 'Low Priority', 'color' => 'green', 'urgency' => 'Can be scheduled for later'],
            default => ['text' => 'Unknown Priority', 'color' => 'gray', 'urgency' => 'Priority not set'],
        };
    }

    /**
     * Get recommendation category information
     */
    public function getCategoryInfo(): array
    {
        return match ($this->type) {
            'code_organization' => [
                'name' => 'Code Organization',
                'icon' => '📁',
                'description' => 'Improvements to code structure and organization',
            ],
            'performance' => [
                'name' => 'Performance',
                'icon' => '⚡',
                'description' => 'Optimizations to improve application performance',
            ],
            'quality_assurance' => [
                'name' => 'Quality Assurance',
                'icon' => '✅',
                'description' => 'Enhancements to code quality and testing',
            ],
            'development_process' => [
                'name' => 'Development Process',
                'icon' => '🔄',
                'description' => 'Improvements to development workflow and processes',
            ],
            'monitoring' => [
                'name' => 'Monitoring',
                'icon' => '📊',
                'description' => 'Tools and processes for ongoing system monitoring',
            ],
            default => [
                'name' => 'General',
                'icon' => '📋',
                'description' => 'General maintenance recommendation',
            ],
        };
    }

    /**
     * Check if recommendation is urgent (high priority)
     */
    public function isUrgent(): bool
    {
        return $this->priority === 'high';
    }

    /**
     * Get estimated effort in hours (parse from string)
     */
    public function getEstimatedHours(): array
    {
        preg_match('/(\d+)(?:-(\d+))?\s*hours?/', $this->estimatedEffort, $matches);
        
        if (empty($matches)) {
            return ['min' => 0, 'max' => 0];
        }
        
        $min = (int) $matches[1];
        $max = isset($matches[2]) ? (int) $matches[2] : $min;
        
        return ['min' => $min, 'max' => $max];
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'priority' => $this->priority,
            'title' => $this->title,
            'description' => $this->description,
            'action_items' => $this->actionItems,
            'estimated_effort' => $this->estimatedEffort,
            'expected_benefit' => $this->expectedBenefit,
            'tags' => $this->tags,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'priority_level' => $this->getPriorityLevel(),
            'formatted_priority' => $this->getFormattedPriority(),
            'category_info' => $this->getCategoryInfo(),
            'is_urgent' => $this->isUrgent(),
            'estimated_hours' => $this->getEstimatedHours(),
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}