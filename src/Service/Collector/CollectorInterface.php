<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Collector;

interface CollectorInterface
{
    public function start(string $identifier): void;
    public function collect(string $identifier): array;
}
