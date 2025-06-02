<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Model;

final class PerformanceResult
{
    private int $responseTime = 0;
    private int $memoryUsage = 0;
    private string $route = '';
    private string $method = '';
    private int $statusCode = 200;
    private ?int $cognitiveComplexity = null;
    private array $collectorData = [];
    private array $analysisResults = [];
    private array $metadata = [];

    public function getResponseTime(): int
    {
        return $this->responseTime;
    }

    public function setResponseTime(int $responseTime): self
    {
        $this->responseTime = $responseTime;
        return $this;
    }

    public function getMemoryUsage(): int
    {
        return $this->memoryUsage;
    }

    public function setMemoryUsage(int $memoryUsage): self
    {
        $this->memoryUsage = $memoryUsage;
        return $this;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getCognitiveComplexity(): ?int
    {
        return $this->cognitiveComplexity;
    }

    public function setCognitiveComplexity(?int $cognitiveComplexity): self
    {
        $this->cognitiveComplexity = $cognitiveComplexity;
        return $this;
    }

    public function addCollectorData(string $collector, array $data): self
    {
        $this->collectorData[$collector] = $data;
        return $this;
    }

    public function getCollectorData(string $collector = null, $default = null): mixed
    {
        if ($collector === null) {
            return $this->collectorData;
        }

        return $this->collectorData[$collector] ?? $default;
    }

    public function addAnalysisResult(string $analyzer, AnalysisResult $result): self
    {
        $this->analysisResults[$analyzer] = $result;
        return $this;
    }

    public function getAnalysisResults(): array
    {
        return $this->analysisResults;
    }

    public function hasIssues(): bool
    {
        foreach ($this->analysisResults as $result) {
            if ($result->hasIssues()) {
                return true;
            }
        }
        return false;
    }

    public function getAllIssues(): array
    {
        $issues = [];
        foreach ($this->analysisResults as $analyzer => $result) {
            foreach ($result->getIssues() as $type => $issue) {
                $issues[] = array_merge($issue, ['analyzer' => $analyzer, 'type' => $type]);
            }
        }
        return $issues;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function addMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }
}
