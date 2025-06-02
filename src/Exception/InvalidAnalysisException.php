<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Exception;

use RuntimeException;

final class InvalidAnalysisException extends RuntimeException
{
    public function __construct(
        string $analyzer,
        string $message = ''
    ) {
        $message = $message ?: sprintf('Invalid analysis performed by %s', $analyzer);
        parent::__construct($message);
    }
}
