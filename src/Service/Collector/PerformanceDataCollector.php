<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Collector;

use AA\PerformanceAnalyzer\Service\PerformanceTracker;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PerformanceDataCollector extends AbstractDataCollector
{
    public function __construct(
        private readonly PerformanceTracker $performanceTracker
    ) {}

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        if (!$this->performanceTracker->isEnabled()) {
            $this->data = ['enabled' => false];
            return;
        }

        $identifier = sprintf('request_%s_%s_%s', $request->getMethod(), $request->getPathInfo(), uniqid());
        $result = $this->performanceTracker->stopTracking($identifier, $request, $response);

        $queries = $result->getCollectorData('database_queries', []);
        $this->data = [
            'enabled' => true,
            'thresholds' => $this->performanceTracker->getThresholds(),
            'request_id' => $request->headers->get('X-Request-ID', uniqid()),
            'response_time' => $result->getResponseTime(),
            'memory_usage' => $result->getMemoryUsage(),
            'query_count' => count($queries),
            'query_time' => array_sum(array_column($queries, 'duration')),
            'issues' => $result->getAllIssues(),
            'cognitive_complexity' => $result->getCognitiveComplexity(),
            'route' => $result->getRoute(),
            'n_plus_one_patterns' => $this->extractNPlusOneIssues($result),
        ];
    }

    private function extractNPlusOneIssues($result): array
    {
        return array_filter(
            $result->getAllIssues(),
            fn($issue) => $issue['type'] === 'n_plus_one'
        );
    }

    public function getResponseTime(): int
    {
        return $this->data['response_time'] ?? 0;
    }
    public function getMemoryUsage(): int
    {
        return $this->data['memory_usage'] ?? 0;
    }
    public function getQueryCount(): int
    {
        return $this->data['query_count'] ?? 0;
    }
    public function getQueryTime(): float
    {
        return $this->data['query_time'] ?? 0.0;
    }
    public function getIssues(): array
    {
        return $this->data['issues'] ?? [];
    }
    public function getCognitiveComplexity(): ?int
    {
        return $this->data['cognitive_complexity'] ?? null;
    }
    public function getRoute(): string
    {
        return $this->data['route'] ?? 'unknown';
    }
    public function getNPlusOnePatterns(): array
    {
        return $this->data['n_plus_one_patterns'] ?? [];
    }
    public function isEnabled(): bool
    {
        return $this->data['enabled'] ?? false;
    }
    public function getThresholds(): array
    {
        return $this->data['thresholds'] ?? [];
    }
    public function getRequestId(): string
    {
        return $this->data['request_id'] ?? '';
    }
    public function getName(): string
    {
        return 'symfony_performance_analyzer';
    }
    public static function getTemplate(): ?string
    {
        return '@SymfonyPerformanceAnalyzer/profiler/performance.html.twig';
    }
}
