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
        $this->data = [
            'enabled' => $this->performanceTracker->isEnabled(),
            'thresholds' => $this->performanceTracker->getThresholds(),
            'request_id' => $request->headers->get('X-Request-ID', uniqid()),
        ];
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
        return '@SymfonyPerformanceAnalyzer/toolbar/performance.html.twig';
    }
}
