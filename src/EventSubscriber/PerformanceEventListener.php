<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\EventSubscriber;

use AA\PerformanceAnalyzer\Event\PerformanceDataEvent;
use Psr\Log\LoggerInterface;

final class PerformanceEventListener
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function onPerformanceData(PerformanceDataEvent $event): void
    {
        $result = $event->getPerformanceResult();
        if ($result->hasIssues()) {
            $this->logger->warning('Performance issues detected', [
                'route' => $result->getRoute(),
                'issues' => $result->getAllIssues()
            ]);
        }
    }
}
