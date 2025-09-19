<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class RiskMetadata extends Data
{
    public function __construct(
        public ?int $totalScore = null,
        public ?array $breakdown = null, // Individual analyzer scores
        public ?array $reasons = null,
        public ?array $features = null,
        public ?string $analyzedAt = null,
        public ?bool $llmUsed = false,
        public ?string $llmResponse = null,
    ) {}

    /**
     * Get formatted score breakdown for display
     */
    public function getFormattedBreakdown(): array
    {
        if (!$this->breakdown) {
            return [];
        }

        return [
            'Email Risk' => [
                'score' => $this->breakdown['email']['score'] ?? 0,
                'weight' => 0.25,
                'weighted' => ($this->breakdown['email']['score'] ?? 0) * 0.25,
                'reasons' => $this->breakdown['email']['reasons'] ?? [],
            ],
            'Phone Risk' => [
                'score' => $this->breakdown['phone']['score'] ?? 0,
                'weight' => 0.25,
                'weighted' => ($this->breakdown['phone']['score'] ?? 0) * 0.25,
                'reasons' => $this->breakdown['phone']['reasons'] ?? [],
            ],
            'Name Risk' => [
                'score' => $this->breakdown['name']['score'] ?? 0,
                'weight' => 0.15,
                'weighted' => ($this->breakdown['name']['score'] ?? 0) * 0.15,
                'reasons' => $this->breakdown['name']['reasons'] ?? [],
            ],
            'IP Risk' => [
                'score' => $this->breakdown['ip']['score'] ?? 0,
                'weight' => 0.20,
                'weighted' => ($this->breakdown['ip']['score'] ?? 0) * 0.20,
                'reasons' => $this->breakdown['ip']['reasons'] ?? [],
            ],
            'Behavioral Risk' => [
                'score' => $this->breakdown['behavioral']['score'] ?? 0,
                'weight' => 0.15,
                'weighted' => ($this->breakdown['behavioral']['score'] ?? 0) * 0.15,
                'reasons' => $this->breakdown['behavioral']['reasons'] ?? [],
            ],
        ];
    }

    /**
     * Get risk level label based on score
     */
    public function getRiskLevel(): string
    {
        return match (true) {
            $this->totalScore >= 70 => 'High Risk',
            $this->totalScore >= 30 => 'Medium Risk',
            default => 'Low Risk',
        };
    }

    /**
     * Get risk level color for UI
     */
    public function getRiskLevelColor(): string
    {
        return match (true) {
            $this->totalScore >= 70 => 'danger',
            $this->totalScore >= 30 => 'warning',
            default => 'success',
        };
    }
}