<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Analyzer;

use AA\PerformanceAnalyzer\Model\AnalysisResult;
use AA\PerformanceAnalyzer\Model\PerformanceResult;
use AA\PerformanceAnalyzer\Utils\ComplexityCalculator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AA\PerformanceAnalyzer\Exception\InvalidAnalysisException;

final class CognitiveComplexityAnalyzer implements AnalyzerInterface
{
    public function __construct(
        private readonly ComplexityCalculator $complexityCalculator,
        private readonly int $maxComplexity = 10
    ) {}

    public function analyze(Request $request, Response $response, PerformanceResult $result): AnalysisResult
    {
        $analysisResult = new AnalysisResult();

        $controller = $request->attributes->get('_controller');
        if (!$controller) {
            throw new InvalidAnalysisException('CognitiveComplexityAnalyzer', 'No controller specified');
        }

        try {
            $complexity = $this->complexityCalculator->calculateControllerComplexity($controller);

            if ($complexity > $this->maxComplexity) {
                $analysisResult->addIssue('cognitive_complexity', [
                    'controller' => $controller,
                    'complexity' => $complexity,
                    'max_allowed' => $this->maxComplexity,
                    'message' => "Controller complexity ({$complexity}) exceeds maximum allowed ({$this->maxComplexity})",
                    'severity' => $this->calculateSeverity($complexity)
                ]);
            }

            $result->setCognitiveComplexity($complexity);
        } catch (\Exception $e) {
            throw new InvalidAnalysisException('CognitiveComplexityAnalyzer', $e->getMessage());
        }

        return $analysisResult;
    }

    private function calculateSeverity(int $complexity): string
    {
        if ($complexity > $this->maxComplexity * 2) {
            return 'high';
        }

        if ($complexity > $this->maxComplexity * 1.5) {
            return 'medium';
        }

        return 'low';
    }
}
