<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Analyzer;

use AA\PerformanceAnalyzer\Exception\PerformanceThresholdExceededException;
use AA\PerformanceAnalyzer\Model\AnalysisResult;
use AA\PerformanceAnalyzer\Model\PerformanceResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MemoryAnalyzer implements AnalyzerInterface
{
    public function __construct(
        private readonly int $maxMemoryMb = 256
    ) {}

    public function analyze(Request $request, Response $response, PerformanceResult $result): AnalysisResult
    {
        $analysisResult = new AnalysisResult();

        $memoryUsageMb = $result->getMemoryUsage() / 1024 / 1024;

        if ($memoryUsageMb > $this->maxMemoryMb * 2) {
            throw new PerformanceThresholdExceededException('memory_usage', $memoryUsageMb, $this->maxMemoryMb);
        }
        if ($memoryUsageMb > $this->maxMemoryMb) {
            $analysisResult->addIssue('memory_usage', [
                'current_usage_mb' => round($memoryUsageMb, 2),
                'max_allowed_mb' => $this->maxMemoryMb,
                'message' => "Memory usage ({$memoryUsageMb}MB) exceeds threshold ({$this->maxMemoryMb}MB)",
                'severity' => $this->calculateSeverity($memoryUsageMb)
            ]);
        }

        return $analysisResult;
    }

    private function calculateSeverity(float $memoryUsageMb): string
    {
        if ($memoryUsageMb > $this->maxMemoryMb * 2) {
            return 'high';
        }

        if ($memoryUsageMb > $this->maxMemoryMb * 1.5) {
            return 'medium';
        }

        return 'low';
    }
}
