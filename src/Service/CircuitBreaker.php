<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service;

use Psr\Log\LoggerInterface;

/**
 * Implements a simple circuit breaker pattern for external services.
 */
final class CircuitBreaker
{
    private bool $isOpen = false;
    private int $failureCount = 0;
    private ?\DateTimeImmutable $lastFailure = null;

    public function __construct(
        private readonly array $config,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Executes a callback with circuit breaker protection.
     *
     * @param callable $callback The operation to execute
     * @param callable|null $fallback Fallback operation if circuit is open
     * @return mixed Result of the callback or fallback
     * @throws \Exception If the operation fails
     */
    public function execute(callable $callback, ?callable $fallback = null): mixed
    {
        if (!$this->config['enabled']) {
            return $callback();
        }

        if ($this->isOpen()) {
            if ($fallback !== null) {
                return $fallback();
            }
            throw new \RuntimeException('Circuit breaker is open');
        }

        try {
            $result = $callback();
            $this->reset();
            return $result;
        } catch (\Exception $e) {
            $this->recordFailure();
            $this->logger->error('Circuit breaker failure', [
                'exception' => $e->getMessage(),
                'failure_count' => $this->failureCount
            ]);
            if ($fallback !== null) {
                return $fallback();
            }
            throw $e;
        }
    }

    /**
     * Checks if the circuit is open.
     *
     * @return bool Whether the circuit is open
     */
    private function isOpen(): bool
    {
        if (!$this->isOpen) {
            return false;
        }

        $retryTimeout = $this->config['retry_timeout'] ?? 60;
        if ($this->lastFailure && (new \DateTimeImmutable())->getTimestamp() - $this->lastFailure->getTimestamp() > $retryTimeout) {
            $this->reset();
            return false;
        }

        return true;
    }

    /**
     * Records a failure and opens the circuit if threshold is reached.
     */
    private function recordFailure(): void
    {
        $this->failureCount++;
        $this->lastFailure = new \DateTimeImmutable();

        if ($this->failureCount >= ($this->config['failure_threshold'] ?? 5)) {
            $this->isOpen = true;
        }
    }

    /**
     * Resets the circuit breaker state.
     */
    private function reset(): void
    {
        $this->isOpen = false;
        $this->failureCount = 0;
        $this->lastFailure = null;
    }
}
