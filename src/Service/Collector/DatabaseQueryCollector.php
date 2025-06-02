<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Collector;

use Doctrine\DBAL\Logging\SQLLogger;

final class DatabaseQueryCollector implements CollectorInterface, SQLLogger
{
    private array $queries = [];
    private array $currentQueries = [];
    private ?float $start = null;

    public function start(string $identifier): void
    {
        $this->currentQueries[$identifier] = [];
    }

    public function collect(string $identifier): array
    {
        return $this->currentQueries[$identifier] ?? [];
    }

    public function startQuery($sql, ?array $params = null, ?array $types = null): void
    {
        $this->start = microtime(true);
    }

    public function stopQuery(): void
    {
        $duration = $this->start ? microtime(true) - $this->start : 0;
        $this->start = null;

        foreach ($this->currentQueries as $identifier => &$queries) {
            $queries[] = [
                'sql' => func_get_args()[0] ?? '',
                'params' => func_get_args()[1] ?? [],
                'duration' => $duration,
                'timestamp' => microtime(true)
            ];
        }
    }

    public function getAllQueries(): array
    {
        return $this->queries;
    }
}
