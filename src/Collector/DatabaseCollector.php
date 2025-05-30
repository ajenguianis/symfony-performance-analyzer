<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Collector;

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManagerInterface;

class DatabaseCollector
{
    private DebugStack $debugStack;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->debugStack = new DebugStack();
        $entityManager->getConnection()->getConfiguration()->setSQLLogger($this->debugStack);
    }

    public function collect(): array
    {
        $queries = $this->debugStack->queries;
        $totalTime = array_sum(array_column($queries, 'executionMS'));
        $count = count($queries);

        return [
            'total_queries' => $count,
            'total_time' => $totalTime,
            'average_time' => $count > 0 ? $totalTime / $count : 0,
            'n_plus_one_issues' => $this->detectNPlusOne($queries),
        ];
    }

    private function detectNPlusOne(array $queries): array
    {
        $issues = [];
        $patterns = [];
        foreach ($queries as $query) {
            $sql = $query['sql'];
            $patterns[$sql] = ($patterns[$sql] ?? 0) + 1;
        }
        foreach ($patterns as $sql => $count) {
            if ($count > 5 && str_contains(strtolower($sql), 'select')) {
                $issues[] = ['sql' => $sql, 'occurrences' => $count];
            }
        }
        return $issues;
    }
}
