<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Analyzer;

use AA\PerformanceAnalyzer\Collector\DatabaseCollector;
use AA\PerformanceAnalyzer\Collector\MemoryCollector;
use AA\PerformanceAnalyzer\Collector\HttpCollector;
use AA\PerformanceAnalyzer\Collector\CacheCollector;

class PerformanceAnalyzer implements AnalyzerInterface
{
    /** @var AnalyzerInterface[] */
    private array $analyzers;

    public function __construct(
        private DatabaseCollector $databaseCollector,
        private MemoryCollector $memoryCollector,
        private HttpCollector $httpCollector,
        private CacheCollector $cacheCollector,
        private array $config,
        iterable $analyzers = []
    ) {
        $this->analyzers = $analyzers instanceof \Traversable ? iterator_to_array($analyzers) : $analyzers;
    }

    public function analyze(): array
    {
        $data = [];

        if ($this->config['collectors']['database']) {
            $data['database'] = $this->databaseCollector->collect();
        }
        if ($this->config['collectors']['memory']) {
            $data['memory'] = $this->memoryCollector->collect();
        }
        if ($this->config['collectors']['http']) {
            $data['http'] = $this->httpCollector->collect();
        }
        if ($this->config['collectors']['cache']) {
            $data['cache'] = $this->cacheCollector->collect();
        }

        foreach ($this->analyzers as $analyzer) {
            $data[class_basename($analyzer)] = $analyzer->analyze();
        }

        return $data;
    }
}
