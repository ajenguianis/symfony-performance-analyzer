<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Exception;

use RuntimeException;

final class PerformanceThresholdExceededException extends RuntimeException
{
    public function __construct(
        string $metric,
        float $value,
        float $threshold,
        string $message = ''
    ) {
        $message = $message ?: sprintf(
            'Performance threshold exceeded for %s: %.2f (threshold: %.2f)',
            $metric,
            $value,
            $threshold
        );
        parent::__construct($message);
    }
}
