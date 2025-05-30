<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer;

use AA\PerformanceAnalyzer\DependencyInjection\PerformanceAnalyzerExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PerformanceAnalyzerBundle extends Bundle
{
    public function getContainerExtension(): PerformanceAnalyzerExtension
    {
        return new PerformanceAnalyzerExtension();
    }
}
