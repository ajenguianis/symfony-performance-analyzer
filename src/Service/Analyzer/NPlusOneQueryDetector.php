<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Analyzer;

use AA\PerformanceAnalyzer\Model\AnalysisResult;
use AA\PerformanceAnalyzer\Model\PerformanceResult;
use AA\PerformanceAnalyzer\Utils\QueryAnalyzer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NPlusOneQueryDetector implements AnalyzerInterface
{
    private const THRESHOLD_RATIO = 0.8;
    private const MIN_QUERIES = 5;
    public function __construct(
        private readonly QueryAnalyzer $queryAnalyzer
    ) {}
    public function analyze(Request $request, Response $response, PerformanceResult $result): AnalysisResult
    {
        $analysisResult = new AnalysisResult();
        $queries = $result->getCollectorData('database_queries', []);

        foreach ($queries as $query) {
            $analysis = $this->queryAnalyzer->analyzeQuery($query['sql'] ?? '');
            if ($analysis['complexity_score'] > 10) {
                $analysisResult->addIssue('complex_query', [
                    'message' => 'Complex query detected',
                    'sql' => $query['sql'],
                    'complexity' => $analysis['complexity_score'],
                    'severity' => 'medium'
                ]);
            }
        }

        $suspiciousPatterns = $this->detectPatterns($queries);

        if (!empty($suspiciousPatterns)) {
            $analysisResult->addIssue('n_plus_one', [
                'message' => 'Potential N+1 query detected',
                'patterns' => $suspiciousPatterns,
                'total_queries' => count($queries),
                'severity' => $this->calculateSeverity(count($queries), count($suspiciousPatterns))
            ]);
        }

        return $analysisResult;
    }

    private function detectPatterns(array $queries): array
    {
        $patterns = [];
        $queryGroups = [];

        foreach ($queries as $query) {
            $normalizedQuery = $this->normalizeQuery($query['sql'] ?? '');
            $queryGroups[$normalizedQuery][] = $query;
        }

        foreach ($queryGroups as $normalizedQuery => $group) {
            if (count($group) >= self::MIN_QUERIES) {
                $uniqueParams = array_unique(array_map(
                    fn($q) => json_encode($q['params'] ?? []),
                    $group
                ));

                $ratio = count($uniqueParams) / count($group);
                if ($ratio >= self::THRESHOLD_RATIO) {
                    $patterns[] = [
                        'query' => $normalizedQuery,
                        'occurrences' => count($group),
                        'unique_params' => count($uniqueParams),
                        'ratio' => $ratio
                    ];
                }
            }
        }

        return $patterns;
    }

    private function normalizeQuery(string $query): string
    {
        $query = preg_replace('/\s+/', ' ', trim($query));
        $query = preg_replace('/\b\d+\b/', '?', $query);
        $query = preg_replace('/\'[^\']*\'/', '?', $query);
        $query = preg_replace('/\"[^\"]*\"/', '?', $query);

        return strtoupper($query);
    }

    private function calculateSeverity(int $totalQueries, int $suspiciousPatterns): string
    {
        $ratio = $suspiciousPatterns / $totalQueries;

        if ($ratio > 0.5) {
            return 'high';
        }

        if ($ratio > 0.3) {
            return 'medium';
        }

        return 'low';
    }
}
