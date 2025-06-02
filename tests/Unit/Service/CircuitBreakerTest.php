<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Service;

use AA\PerformanceAnalyzer\Service\CircuitBreaker;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CircuitBreakerTest extends TestCase
{
    private CircuitBreaker $circuitBreaker;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->circuitBreaker = new CircuitBreaker([
            'enabled' => true,
            'failure_threshold' => 2,
            'retry_timeout' => 60
        ], $this->logger);
    }

    public function testExecuteSuccess(): void
    {
        $result = $this->circuitBreaker->execute(fn() => 'success');
        $this->assertEquals('success', $result);
    }

    public function testExecuteFailureOpensCircuit(): void
    {
        $this->logger->expects($this->exactly(2))->method('error');

        try {
            $this->circuitBreaker->execute(fn() => throw new \Exception('Failed'));
        } catch (\Exception $e) {
        }

        try {
            $this->circuitBreaker->execute(fn() => throw new \Exception('Failed'));
        } catch (\Exception $e) {
        }

        $this->expectException(\RuntimeException::class);
        $this->circuitBreaker->execute(fn() => 'should not execute');
    }

    public function testExecuteWithFallback(): void
    {
        $this->logger->expects($this->exactly(2))->method('error');

        try {
            $this->circuitBreaker->execute(fn() => throw new \Exception('Failed'));
        } catch (\Exception $e) {
        }

        try {
            $this->circuitBreaker->execute(fn() => throw new \Exception('Failed'));
        } catch (\Exception $e) {
        }

        $result = $this->circuitBreaker->execute(
            fn() => 'should not execute',
            fn() => 'fallback'
        );
        $this->assertEquals('fallback', $result);
    }
}
