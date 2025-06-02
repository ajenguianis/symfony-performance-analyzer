<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service;

use AA\PerformanceAnalyzer\Repository\PerformanceLogRepository;
use AA\PerformanceAnalyzer\Repository\PerformanceStatRepository;

final class PerformanceSummary
{
    public function __construct(
        private readonly PerformanceLogRepository $logRepository,
        private readonly PerformanceStatRepository $statRepository
    ) {
    }

    public function generateSummary(): array
    {
        $recentLogs = $this->logRepository->findRecentLogs(1000);
        $routeStats = $this->logRepository->getAverageMetricsByRoute();

        $totalRequests = count($recentLogs);
        $avgResponseTime = $totalRequests > 0 
            ? array_sum(array_column($recentLogs, 'responseTime')) / $totalRequests 
            : 0;
        
        $avgMemoryUsage = $totalRequests > 0 
            ? array_sum(array_column($recentLogs, 'memoryUsage')) / $totalRequests 
            : 0;
        
        $slowQueries = array_filter($recentLogs, fn($log) => $log->getResponseTime() > 500);
        $memoryIssues = array_filter($recentLogs, fn($log) => $log->getMemoryUsage() > 256 * 1024 * 1024);

        return [
            'total_requests' => $totalRequests,
            'avg_response_time' => round($avgResponseTime, 2),
            'avg_memory_usage' => round($avgMemoryUsage / 1024 / 1024, 2), // MB
            'slow_queries_count' => count($slowQueries),
            'memory_issues_count' => count($memoryIssues),
            'routes_analyzed' => count($routeStats),
            'generated_at' => new \DateTimeImmutable(),
        ];
    }

    public function generateRouteAnalysis(string $route): array
    {
        $logs = $this->logRepository->findByRoute($route, 100);
        
        if (empty($logs)) {
            return ['error' => 'No data found for route'];
        }

        $responseTimes = array_map(fn($log) => $log->getResponseTime(), $logs);
        $memoryUsages = array_map(fn($log) => $log->getMemoryUsage(), $logs);
        $queryCounts = array_map(fn($log) => $log->getQueryCount(), $logs);

        return [
            'route' => $route,
            'total_requests' => count($logs),
            'response_time' => [
                'avg' => round(array_sum($responseTimes) / count($responseTimes), 2),
                'min' => min($responseTimes),
                'max' => max($responseTimes),
                'p95' => $this->calculatePercentile($responseTimes, 95),
            ],
            'memory_usage' => [
                'avg' => round(array_sum($memoryUsages) / count($memoryUsages) / 1024 / 1024, 2),
                'min' => round(min($memoryUsages) / 1024 / 1024, 2),
                'max' => round(max($memoryUsages) / 1024 / 1024, 2),
                'p95' => round($this->calculatePercentile($memoryUsages, 95) / 1024 / 1024, 2),
            ],
            'queries' => [
                'avg' => round(array_sum($queryCounts) / count($queryCounts), 2),
                'min' => min($queryCounts),
                'max' => max($queryCounts),
            ],
        ];
    }

    private function calculatePercentile(array $values, int $percentile): float
    {
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        
        if ($index === (int) $index) {
            return $values[$index];
        }
        
        $lower = $values[(int) $index];
        $upper = $values[(int) $index + 1];
        $fraction = $index - (int) $index;
        
        return $lower + $fraction * ($upper - $lower);
    }
}