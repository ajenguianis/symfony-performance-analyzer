<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CopilotRecommendationService
{
    private const COPILOT_ENDPOINT = 'https://api.githubcopilot.com/v1/engine';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $copilotToken
    ) {}

    public function getPerformanceRecommendations(array $performanceData): array
    {
        $prompt = $this->createPrompt($performanceData);

        try {
            $response = $this->httpClient->request('POST', self::COPILOT_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->copilotToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'prompt' => $prompt,
                    'max_tokens' => 500,
                    'temperature' => 0.7,
                ],
            ]);

            $content = $response->toArray();
            return $this->parseCopilotResponse($content['choices'][0]['text'] ?? '');
        } catch (\Exception $e) {
            return ['error' => 'Unable to get Copilot recommendations: ' . $e->getMessage()];
        }
    }

    private function createPrompt(array $data): string
    {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);

        return <<<PROMPT
        Analyze these Symfony performance metrics and provide specific recommendations for improvement:
        
        $jsonData
        
        Focus on:
        - Database query optimization
        - Memory usage reduction
        - Cache strategies
        - General PHP performance best practices
        - Symfony-specific optimizations
        
        Provide your recommendations in markdown format with clear sections.
        PROMPT;
    }

    private function parseCopilotResponse(string $response): array
    {
        return [
            'source' => 'GitHub Copilot',
            'recommendations' => $response,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ];
    }
}
