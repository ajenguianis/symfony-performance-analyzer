<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Analyzer;

interface AnalyzerInterface
{
    public function analyze(): array;
}
