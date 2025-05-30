<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AiRecommendationService
{
    private const PROMPT_TEMPLATE = <<<EOT
You are an expert PHP and Symfony performance optimizer. Analyze the following performance and code analysis data and provide specific, actionable suggestions to improve performance and code quality. Focus on:
- Optimizing database queries (e.g., N+1 issues).
- Replacing unnecessary loops with array functions.
- Reducing cognitive complexity.
- Improving cache usage.

Data:
%s

Provide up to 5 recommendations in JSON format:
```json
[
    {
        "issue": "Description of the issue",
        "suggestion": "Specific action to take",
        "priority": "low|medium|high",
        "context": "Relevant details (e.g., file, line number)"
    }
]
```
EOT;

    public function __construct(
        private HttpClientInterface $client,
        private ParameterBagInterface $parameters,
        #[Autowire('%env(AI_API_KEY)%')] private ?string $apiKey,
        #[Autowire('%env(AI_ENDPOINT)%')] private ?string $endpoint,
        #[Autowire('%env(AI_MODEL)%')] private ?string $model,
        #[Autowire('%aa_performance_analyzer.ai_integration.enabled%')] private bool $aiIntegrationEnabled
    ) {}

    public function getRecommendations(array $performanceData, array $codeAnalysisData): array
    {
        $recommendations = [];

        // Fallback: Static rules if AI is disabled or fails
        $recommendations = array_merge($recommendations, $this->getFallbackRecommendations($performanceData, $codeAnalysisData));

        if (!$this->isAiEnabled()) {
            return $recommendations;
        }

        try {
            // Prepare data for AI
            $dataSummary = $this->prepareDataSummary($performanceData, $codeAnalysisData);
            $prompt = sprintf(self::PROMPT_TEMPLATE, $dataSummary);

            // Send request to AI endpoint
            $response = $this->client->request('POST', $this->endpoint, [
                'headers' => [
                    'Authorization' => "Bearer $this->apiKey",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model ?? 'gpt-4', // Fallback to 'gpt-4' if not set
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a performance optimizer assistant.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
            ]);

            $aiRecommendations = $response->toArray()['choices'][0]['message']['content'];
            $aiRecommendations = json_decode($aiRecommendations, true);

            if (is_array($aiRecommendations)) {
                $recommendations = array_merge($recommendations, $aiRecommendations);
            }
        } catch (\Exception $e) {
            // Log error and rely on fallback recommendations
            $this->logError($e->getMessage());
        }

        // Limit to 5 recommendations, sorted by priority
        usort($recommendations, fn($a, $b) => $this->priorityToInt($b['priority']) <=> $this->priorityToInt($a['priority']));
        return array_slice($recommendations, 0, 5);
    }

    private function isAiEnabled(): bool
    {
        return $this->aiIntegrationEnabled &&
            $this->apiKey !== null && $this->apiKey !== '' &&
            $this->endpoint !== null && $this->endpoint !== '' &&
            $this->model !== null && $this->model !== '';
    }

    private function prepareDataSummary(array $performanceData, array $codeAnalysisData): string
    {
        $summary = [];

        // Performance data
        if (isset($performanceData['database'])) {
            $summary[] = "Database: {$performanceData['database']['total_queries']} queries, "
                . "{$performanceData['database']['total_time']}ms total time, "
                . count($performanceData['database']['n_plus_one_issues']) . " N+1 issues detected.";
        }
        if (isset($performanceData['memory'])) {
            $peakUsageMb = number_format($performanceData['memory']['peak_usage'] / 1024 / 1024, 2);
            $summary[] = "Memory: Peak usage {$peakUsageMb}MB.";
        }

        // Code analysis data
        foreach ($codeAnalysisData as $file => $analysis) {
            $summary[] = "File: $file, Cognitive Complexity: {$analysis['cognitive_complexity']}, "
                . "Issues: " . json_encode($analysis['issues']);
        }

        return implode("\n", $summary);
    }

    private function getFallbackRecommendations(array $data, array $codeAnalysisData): array
    {
        $recommendations = [];

        // Database N+1 issues
        if (isset($data['database']['n_plus_one']) && !empty($data['database']['n_plus_one'])) {
            foreach ($data['database']['n_plus_one'] as $issue) {
                $recommendations[] = [
                    'issue' => 'N+1 query issue detected',
                    'suggestion' => "Use JOIN or eager loading for query: {$issue['sql']}",
                    'priority' => 'high',
                    'context' => "Occurrences: {$issue['occurrences']}",
                ];
            }
        }

        // High query count
        if (isset($data['database']['total_queries']) && $data['database']['total_queries'] > 50) {
            $recommendations[] = [
                'issue' => 'High database query count',
                'suggestion' => 'Consider optimizing queries or caching results.',
                'priority' => 'medium',
                'context' => "Total queries: {$data['database']['total_queries']}",
            ];
        }

        // Unnecessary loops
        foreach ($codeAnalysisData as $file => $analysis) {
            foreach ($analysis['issues'] as $issue) {
                if (str_contains($issue['message'], 'Unnecessary foreach')) {
                    $recommendations[] = [
                        'issue' => 'Unnecessary foreach loop detected',
                        'suggestion' => 'Replace with array_column, array_map, or array_filter.',
                        'priority' => 'medium',
                        'context' => "File: $file, Line: {$issue['line']}",
                    ];
                }
            }

            // High cognitive complexity
            if ($analysis['cognitive_complexity'] > 15) {
                $recommendations[] = [
                    'issue' => 'High cognitive complexity',
                    'suggestion' => 'Refactor to reduce nesting and simplify control structures.',
                    'priority' => 'high',
                    'context' => "File: $file, Complexity: {$analysis['cognitive_complexity']}",
                ];
            }
        }

        return $recommendations;
    }

    private function priorityToInt(string $priority): int
    {
        return match ($priority) {
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        };
    }

    private function logError(string $message): void
    {
        $filesystem = new Filesystem();
        $logDir = $this->parameters->get('kernel.logs_dir');
        $filesystem->appendToFile(
            "$logDir/ai_recommendations.log",
            sprintf("[%s] Error: %s\n", date('Y-m-d H:i:s'), $message)
        );
    }
}
