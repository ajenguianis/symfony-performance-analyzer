<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Analyzer;

use AA\PerformanceAnalyzer\Analyzer\PerformanceAnalyzer;
use PHPUnit\Framework\TestCase;

class PerformanceAnalyzerTest extends TestCase
{
    public function testAnalyze(): void
    {
        $analyzer = new PerformanceAnalyzer(
            $this->createMock(\AA\PerformanceAnalyzer\Collector\DatabaseCollector::class),
            $this->createMock(\AA\PerformanceAnalyzer\Collector\MemoryCollector::class),
            $this->createMock(\AA\PerformanceAnalyzer\Collector\HttpCollector::class),
            $this->createMock(\AA\PerformanceAnalyzer\Collector\CacheCollector::class),
            ['collectors' => ['database' => true, 'memory' => true, 'http' => true, 'cache' => true]]
        );
        $result = $analyzer->analyze();
        $this->assertIsArray($result);
    }
}
