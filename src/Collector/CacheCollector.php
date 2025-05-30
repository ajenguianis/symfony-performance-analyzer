<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Collector;

use Psr\Cache\CacheItemPoolInterface;

class CacheCollector
{
    private array $stats = [];

    public function __construct(private CacheItemPoolInterface $cache) {}

    public function collect(): void
    {
        $this->stats['hits'] = 0;
        $this->stats['misses'] = 0;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function reset(): void
    {
        $this->stats = [];
    }
}
