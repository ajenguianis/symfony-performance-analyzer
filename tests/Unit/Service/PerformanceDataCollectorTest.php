<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Service;

use AA\PerformanceAnalyzer\Model\PerformanceResult;
use AA\PerformanceAnalyzer\Service\PerformanceDataCollector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PerformanceDataCollectorTest extends TestCase
{
    private PerformanceDataCollector $collector;
    private MockObject $performanceResult;

    protected function setUp(): void
    {
        $this->performanceResult = $this->createMock(PerformanceResult::class);
        $this->collector = new PerformanceDataCollector($this->performanceResult);
    }

    public function testCollect(): void
    {
        $request = Request::create('/test');
        $response = new Response();

        $this->performanceResult->method('getResponseTime')->willReturn(100);
        $this->performanceResult->method('getMemoryUsage')->willReturn(1048576);
        $this->performanceResult->method('getCollectorData')->willReturn(['database_queries' => [['sql' => 'SELECT * FROM test']]]);

        $this->collector->collect($request, $response);

        $this->assertEquals(100, $this->collector->getResponseTime());
        $this->assertEquals(1, $this->collector->getMemoryUsageMb());
        $this->assertCount(1, $this->collector->getQueries());
    }

    public function testGetName(): void
    {
        $this->assertEquals('performance', $this->collector->getName());
    }
}
