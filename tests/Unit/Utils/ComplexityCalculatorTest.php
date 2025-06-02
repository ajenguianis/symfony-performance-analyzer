<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Utils;

use AA\PerformanceAnalyzer\Utils\ComplexityCalculator;
use PHPUnit\Framework\TestCase;

final class ComplexityCalculatorTest extends TestCase
{
    private ComplexityCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ComplexityCalculator();
    }

    public function testCalculateControllerComplexityNonZero(): void
    {
        $complexity = $this->calculator->calculateControllerComplexity('App\Controller\ComplexController::index');

        $this->assertIsInt($complexity);
        $this->assertGreaterThan(0, $complexity, 'Complexity should be greater than 0 for complex logic');
    }

    public function testCalculateControllerComplexityAlertingCase(): void
    {
        $complexity = $this->calculator->calculateControllerComplexity('App\Controller\ComplexController::index');

        $this->assertGreaterThan(10, $complexity, 'Complexity should exceed 10 for alerting case');
    }

    public function testCalculateControllerComplexitySimpleCase(): void
    {
        $complexity = $this->calculator->calculateControllerComplexity('App\Controller\SimpleController::index');

        $this->assertIsInt($complexity);
        $this->assertLessThanOrEqual(5, $complexity, 'Simple controller should have low complexity');
    }
}
