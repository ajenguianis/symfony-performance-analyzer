<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Analyzer;

use AA\PerformanceAnalyzer\Model\AnalysisResult;
use AA\PerformanceAnalyzer\Model\PerformanceResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface AnalyzerInterface
{
    public function analyze(Request $request, Response $response, PerformanceResult $result): AnalysisResult;
}
