<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Utils;

final class QueryAnalyzer
{

    public function analyzeQuery(string $sql): array
    {
        $analysis = [
            'type' => $this->getQueryType($sql),
            'tables' => $this->extractTables($sql),
            'has_joins' => $this->hasJoins($sql),
            'has_subqueries' => $this->hasSubqueries($sql),
            'complexity_score' => 0,
        ];

        $analysis['complexity_score'] = $this->calculateQueryComplexity($sql, $analysis);

        return $analysis;
    }

    private function getQueryType(string $sql): string
    {
        $sql = trim(strtoupper($sql));

        if (str_starts_with($sql, 'SELECT')) return 'SELECT';
        if (str_starts_with($sql, 'INSERT')) return 'INSERT';
        if (str_starts_with($sql, 'UPDATE')) return 'UPDATE';
        if (str_starts_with($sql, 'DELETE')) return 'DELETE';
        if (str_starts_with($sql, 'CREATE')) return 'CREATE';
        if (str_starts_with($sql, 'ALTER')) return 'ALTER';
        if (str_starts_with($sql, 'DROP')) return 'DROP';

        return 'OTHER';
    }

    private function extractTables(string $sql): array
    {
        $tables = [];

        // Simple table extraction (can be improved)
        if (preg_match_all('/(?:FROM|JOIN|UPDATE|INTO)\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $sql, $matches)) {
            $tables = array_unique($matches[1]);
        }

        return $tables;
    }

    private function hasJoins(string $sql): bool
    {
        return preg_match('/\b(?:INNER\s+JOIN|LEFT\s+JOIN|RIGHT\s+JOIN|FULL\s+JOIN|JOIN)\b/i', $sql) > 0;
    }

    private function hasSubqueries(string $sql): bool
    {
        return preg_match('/\(\s*SELECT\b/i', $sql) > 0;
    }

    private function calculateQueryComplexity(string $sql, array $analysis): int
    {
        $complexity = 1;

        // Base complexity based on query type
        switch ($analysis['type']) {
            case 'SELECT':
                $complexity += 1;
                break;
            case 'INSERT':
            case 'UPDATE':
            case 'DELETE':
                $complexity += 2;
                break;
            default:
                $complexity += 3;
        }

        // Add complexity for table count
        $complexity += count($analysis['tables']);

        // Add complexity for joins
        if ($analysis['has_joins']) {
            $joinCount = preg_match_all('/\bJOIN\b/i', $sql);
            $complexity += $joinCount * 2;
        }

        // Add complexity for subqueries
        if ($analysis['has_subqueries']) {
            $subqueryCount = preg_match_all('/\(\s*SELECT\b/i', $sql);
            $complexity += $subqueryCount * 3;
        }

        // Add complexity for common expensive operations
        $expensivePatterns = [
            '/\bORDER\s+BY\b/i' => 1,
            '/\bGROUP\s+BY\b/i' => 2,
            '/\bHAVING\b/i' => 2,
            '/\bDISTINCT\b/i' => 2,
            '/\b(?:SUM|COUNT|AVG|MAX|MIN)\s*\(/i' => 1,
        ];

        foreach ($expensivePatterns as $pattern => $weight) {
            if (preg_match($pattern, $sql)) {
                $complexity += $weight;
            }
        }

        return $complexity;
    }
}
