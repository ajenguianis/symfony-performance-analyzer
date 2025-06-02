<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Service\Analyzer;

use AA\PerformanceAnalyzer\Exception\InvalidAnalysisException;
use AA\PerformanceAnalyzer\Model\AnalysisResult;
use AA\PerformanceAnalyzer\Service\Analyzer\CognitiveComplexityAnalyzer;
use AA\PerformanceAnalyzer\Utils\ComplexityCalculator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CognitiveComplexityAnalyzerTest extends TestCase
{
    private CognitiveComplexityAnalyzer $analyzer;
    private MockObject $calculator;

    protected function setUp(): void
    {
        $this->calculator = $this->createMock(ComplexityCalculator::class);
        $this->analyzer = new CognitiveComplexityAnalyzer($this->calculator, 10);
    }

    public function testAnalyzeThrowsExceptionForNoController(): void
    {
        $this->expectException(InvalidAnalysisException::class);
        $request = Request::create('/test');
        $response = new Response();
        $this->analyzer->analyze($request, $response, new \AA\PerformanceAnalyzer\Model\PerformanceResult());
    }

    public function testAnalyzeHighComplexity(): void
    {
        $request = Request::create('/test');
        $request->attributes->set('_controller', 'App\Controller\ComplexController::index');
        $response = new Response();
        $this->calculator->method('calculateControllerComplexity')->willReturn(15);

        $result = $this->analyzer->analyze($request, $response, new \AA\PerformanceAnalyzer\Model\PerformanceResult());

        $this->assertTrue($result->hasIssues());
        $this->assertEquals('high_complexity', $result->getIssues()['high_complexity'][0]['type']);
    }
}
