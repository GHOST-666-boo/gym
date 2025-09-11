<?php

namespace App\Services\Cleanup\Models;

class RiskAssessment
{
    public string $type;
    public string $severity;
    public string $title;
    public string $description;
    public string $potentialImpact;
    public array $mitigationStrategies;
    public string $likelihood;
    public string $detectionDifficulty;
    public array $affectedFiles;
    public array $metadata;
    public \DateTime $createdAt;

    public function __construct(array $data = [])
    {
        $this->type = $data['type'] ?? '';
        $this->severity = $data['severity'] ?? 'medium';
        $this->title = $data['title'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->potentialImpact = $data['potential_impact'] ?? '';
        $this->mitigationStrategies = $data['mitigation_strategies'] ?? [];
        $this->likelihood = $data['likelihood'] ?? 'medium';
        $this->detectionDifficulty = $data['detection_difficulty'] ?? 'medium';
        $this->affectedFiles = $data['affected_files'] ?? [];
        $this->metadata = $data['metadata'] ?? [];
        $this->createdAt = $data['created_at'] ?? new \DateTime();
    }

    /**
     * Get risk score (0-100)
     */
    public function getRiskScore(): int
    {
        $severityScore = match ($this->severity) {
            'critical' => 40,
            'high' => 30,
            'medium' => 20,
            'low' => 10,
            default => 5,
        };

        $likelihoodScore = match ($this->likelihood) {
            'very_high' => 30,
            'high' => 25,
            'medium' => 15,
            'low' => 10,
            'very_low' => 5,
            default => 15,
        };

        $detectionScore = match ($this->detectionDifficulty) {
            'very_high' => 30,
            'high' => 25,
            'medium' => 15,
            'low' => 10,
            'very_low' => 5,
            default => 15,
        };

        return min(100, $severityScore + $likelihoodScore + $detectionScore);
    }

    /**
     * Get risk level based on score
     */
    public function getRiskLevel(): string
    {
        $score = $this->getRiskScore();
        
        if ($score >= 80) {
            return 'Critical';
        } elseif ($score >= 60) {
            return 'High';
        } elseif ($score >= 40) {
            return 'Medium';
        } elseif ($score >= 20) {
            return 'Low';
        } else {
            return 'Minimal';
        }
    }

    /**
     * Get severity information with color coding
     */
    public function getSeverityInfo(): array
    {
        return match ($this->severity) {
            'critical' => [
                'text' => 'Critical',
                'color' => 'red',
                'icon' => '🚨',
                'action' => 'Immediate action required',
            ],
            'high' => [
                'text' => 'High',
                'color' => 'orange',
                'icon' => '⚠️',
                'action' => 'Urgent attention needed',
            ],
            'medium' => [
                'text' => 'Medium',
                'color' => 'yellow',
                'icon' => '⚡',
                'action' => 'Should be addressed',
            ],
            'low' => [
                'text' => 'Low',
                'color' => 'blue',
                'icon' => 'ℹ️',
                'action' => 'Monitor and review',
            ],
            default => [
                'text' => 'Unknown',
                'color' => 'gray',
                'icon' => '❓',
                'action' => 'Assess severity',
            ],
        };
    }

    /**
     * Get risk type information
     */
    public function getTypeInfo(): array
    {
        return match ($this->type) {
            'file_deletion' => [
                'name' => 'File Deletion',
                'category' => 'Data Loss',
                'description' => 'Risks related to removing files from the system',
            ],
            'code_modification' => [
                'name' => 'Code Modification',
                'category' => 'Functionality',
                'description' => 'Risks from modifying existing code structure',
            ],
            'refactoring' => [
                'name' => 'Refactoring',
                'category' => 'Behavior Change',
                'description' => 'Risks from restructuring code organization',
            ],
            'operational' => [
                'name' => 'Operational',
                'category' => 'System Operation',
                'description' => 'Risks affecting system operation and performance',
            ],
            'testing' => [
                'name' => 'Testing',
                'category' => 'Quality Assurance',
                'description' => 'Risks related to testing and validation',
            ],
            default => [
                'name' => 'General',
                'category' => 'Unknown',
                'description' => 'General risk assessment',
            ],
        };
    }

    /**
     * Check if risk requires immediate attention
     */
    public function requiresImmediateAttention(): bool
    {
        return in_array($this->severity, ['critical', 'high']) && 
               in_array($this->likelihood, ['high', 'very_high']);
    }

    /**
     * Get recommended actions based on risk level
     */
    public function getRecommendedActions(): array
    {
        $riskLevel = $this->getRiskLevel();
        
        $baseActions = $this->mitigationStrategies;
        
        $additionalActions = match ($riskLevel) {
            'Critical' => [
                'Stop deployment immediately',
                'Escalate to senior team members',
                'Create detailed incident report',
            ],
            'High' => [
                'Review with team lead before proceeding',
                'Implement additional monitoring',
                'Prepare rollback plan',
            ],
            'Medium' => [
                'Document risk acceptance decision',
                'Schedule follow-up review',
            ],
            'Low', 'Minimal' => [
                'Monitor during normal operations',
            ],
            default => [],
        };
        
        return array_merge($baseActions, $additionalActions);
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'severity' => $this->severity,
            'title' => $this->title,
            'description' => $this->description,
            'potential_impact' => $this->potentialImpact,
            'mitigation_strategies' => $this->mitigationStrategies,
            'likelihood' => $this->likelihood,
            'detection_difficulty' => $this->detectionDifficulty,
            'affected_files' => $this->affectedFiles,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'risk_score' => $this->getRiskScore(),
            'risk_level' => $this->getRiskLevel(),
            'severity_info' => $this->getSeverityInfo(),
            'type_info' => $this->getTypeInfo(),
            'requires_immediate_attention' => $this->requiresImmediateAttention(),
            'recommended_actions' => $this->getRecommendedActions(),
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