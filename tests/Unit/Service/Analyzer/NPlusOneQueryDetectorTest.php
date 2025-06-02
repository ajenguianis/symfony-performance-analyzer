<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Service\Analyzer;

use AA\PerformanceAnalyzer\Model\AnalysisResult;
use AA\PerformanceAnalyzer\Model\PerformanceResult;
use AA\PerformanceAnalyzer\Service\Analyzer\NPlusOneQueryDetector;
use AA\PerformanceAnalyzer\Utils\QueryAnalyzer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NPlusOneQueryDetectorTest extends TestCase
{
    private NPlusOneQueryDetector $detector;
    private MockObject $queryAnalyzer;

    protected function setUp(): void
    {
        $this->queryAnalyzer = $this->createMock(QueryAnalyzer::class);
        $this->detector = new NPlusOneQueryDetector($this->queryAnalyzer);
    }

    public function testAnalyzeDetectsComplexQuery(): void
    {
        $request = Request::create('/test');
        $response = new Response();
        $result = new PerformanceResult();
        $result->setCollectorData('database_queries', [['sql' => 'SELECT * FROM test']]);

        $this->queryAnalyzer->method('analyzeQuery')->willReturn(['complexity_score' => 15]);

        $analysisResult = $this->detector->analyze($request, $response, $result);

        $this->assertInstanceOf(AnalysisResult::class, $analysisResult);
        $this->assertTrue($analysisResult->hasIssues());
        $this->assertEquals('complex_query', $analysisResult->getIssues()['complex_query'][0]['type']);
    }
}
