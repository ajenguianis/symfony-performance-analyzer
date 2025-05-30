<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Collector;

class MemoryCollector
{
    public function collect(): array
    {
        return [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
        ];
    }
}
