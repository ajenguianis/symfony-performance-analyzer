<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Service\Analyzer;

use AA\PerformanceAnalyzer\Exception\PerformanceThresholdExceededException;
use AA\PerformanceAnalyzer\Model\AnalysisResult;
use AA\PerformanceAnalyzer\Model\PerformanceResult;
use AA\PerformanceAnalyzer\Service\Analyzer\MemoryAnalyzer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MemoryAnalyzerTest extends TestCase
{
    private MemoryAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new MemoryAnalyzer(50);
    }

    public function testAnalyzeThrowsExceptionForExcessiveMemory(): void
    {
        $this->expectException(PerformanceThresholdExceededException::class);
        $request = Request::create('/test');
        $response = new Response();
        $result = new PerformanceResult();
        $result->setMemoryUsage(104857600); // 100 MB

        $this->analyzer->analyze($request, $response, $result);
    }

    public function testAnalyzeHighMemory(): void
    {
        $request = Request::create('/test');
        $response = new Response();
        $result = new PerformanceResult();
        $result->setMemoryUsage(52428800); // 50 MB

        $analysisResult = $this->analyzer->analyze($request, $response, $result);

        $this->assertTrue($analysisResult->hasIssues());
        $this->assertEquals('memory_usage', $analysisResult->getIssues()['memory_usage'][0]['type']);
    }
}
