<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Model;

final class AnalysisResult
{
    private array $issues = [];
    private array $suggestions = [];
    private array $metrics = [];

    public function addIssue(string $type, array $data): self
    {
        $this->issues[$type] = $data;
        return $this;
    }

    public function getIssues(): array
    {
        return $this->issues;
    }

    public function hasIssues(): bool
    {
        return !empty($this->issues);
    }

    public function addSuggestion(string $type, string $message): self
    {
        $this->suggestions[$type] = $message;
        return $this;
    }

    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    public function addMetric(string $name, float $value, ?string $unit = null): self
    {
        $this->metrics[$name] = [
            'value' => $value,
            'unit' => $unit
        ];
        return $this;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }
}
