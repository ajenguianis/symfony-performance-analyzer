<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Analyzer;

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManagerInterface;

class QueryAnalyzer
{
    private DebugStack $debugStack;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        $this->debugStack = new DebugStack();
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger($this->debugStack);
    }

    public function analyze(): array
    {
        $queries = $this->debugStack->queries;
        $totalTime = 0;
        $count = count($queries);

        foreach ($queries as $query) {
            $totalTime += $query['executionMS'];
        }

        $nPlusOneIssues = $this->detectNPlusOne($queries);

        return [
            'total_queries' => $count,
            'total_time' => $totalTime,
            'average_time' => $count > 0 ? $totalTime / $count : 0,
            'n_plus_one_issues' => $nPlusOneIssues,
            'queries' => $queries,
        ];
    }
    private function detectNPlusOne(array $queries): array
    {
        $issues = [];
        $queryPatterns = [];
        $similarityThreshold = 0.8;

        // Étape 1: Groupement des requêtes similaires
        foreach ($queries as $index => $queryInfo) {
            $sql = $queryInfo['sql'];
            $foundGroup = false;

            foreach ($queryPatterns as &$pattern) {
                similar_text($this->normalizeSql($sql), $this->normalizeSql($pattern['pattern']), $similarity);

                if ($similarity >= $similarityThreshold) {
                    $pattern['count']++;
                    $pattern['examples'][] = [
                        'sql' => $sql,
                        'params' => $queryInfo['params'] ?? [],
                        'executionMS' => $queryInfo['executionMS'] ?? 0,
                        'index' => $index
                    ];
                    $foundGroup = true;
                    break;
                }
            }

            if (!$foundGroup) {
                $queryPatterns[] = [
                    'pattern' => $sql,
                    'count' => 1,
                    'examples' => [
                        [
                            'sql' => $sql,
                            'params' => $queryInfo['params'] ?? [],
                            'executionMS' => $queryInfo['executionMS'] ?? 0,
                            'index' => $index
                        ]
                    ]
                ];
            }
        }

        // Étape 2: Identification des motifs N+1
        foreach ($queryPatterns as $pattern) {
            if ($this->isPotentialNPlusOne($pattern)) {
                $issues[] = [
                    'type' => 'N+1 Query',
                    'pattern' => $this->normalizeSql($pattern['pattern']),
                    'occurrences' => $pattern['count'],
                    'total_time' => array_sum(array_column($pattern['examples'], 'executionMS')),
                    'examples' => array_slice($pattern['examples'], 0, 3),
                    'suggestion' => $this->getNPlusOneSuggestion($pattern['pattern'])
                ];
            }
        }

        return $issues;
    }

    private function isPotentialNPlusOne(array $pattern): bool
    {
        // Un motif est considéré comme N+1 si:
        // 1. Il apparaît plus de 5 fois
        // 2. C'est une requête SELECT
        // 3. Elle contient une clause WHERE avec un paramètre
        if ($pattern['count'] < 5) {
            return false;
        }

        $sql = strtolower($pattern['pattern']);
        if (!str_contains($sql, 'select')) {
            return false;
        }

        // Vérifie si la requête a un paramètre dans WHERE
        $normalized = $this->normalizeSql($pattern['pattern']);
        if (preg_match('/where\s+[\w\.]+\s*=\s*\?/', $normalized)) {
            return true;
        }

        // Vérifie les requêtes IN avec un seul paramètre
        if (preg_match('/where\s+[\w\.]+\s+in\s*\(\s*\?\s*\)/', $normalized)) {
            return true;
        }

        return false;
    }

    private function normalizeSql(string $sql): string
    {
        // Normalise la requête SQL pour comparaison
        $sql = preg_replace('/\s+/', ' ', $sql); // Remplace les espaces multiples
        $sql = preg_replace('/\s*=\s*/', '=', $sql); // Normalise les espaces autour des =
        $sql = preg_replace('/\(\s*/', '(', $sql); // Normalise les espaces après (
        $sql = preg_replace('/\s*\)/', ')', $sql); // Normalise les espaces avant )
        $sql = trim($sql);

        return strtolower($sql);
    }

    private function getNPlusOneSuggestion(string $sql): string
    {
        $normalized = $this->normalizeSql($sql);

        if (str_contains($normalized, 'join')) {
            return "Vérifiez l'utilisation des jointures et envisagez d'utiliser fetch: EAGER ou une requête optimisée.";
        }

        if (preg_match('/where\s+[\w\.]+\s*=\s*\?/', $normalized)) {
            return "Envisagez d'utiliser DQL avec JOIN FETCH ou Criteria pour charger les relations en une seule requête.";
        }

        if (preg_match('/where\s+[\w\.]+\s+in\s*\(\s*\?\s*\)/', $normalized)) {
            return "Envisagez de regrouper les IDs et d'utiliser une seule requête avec IN (?) puis de mapper les résultats.";
        }

        return "Examinez cette requête récurrente et envisagez de précharger les données nécessaires.";
    }
}
