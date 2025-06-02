<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class PerformanceExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('has_issues', [$this, 'hasIssues']),
            new TwigFilter('map_issues', [$this, 'mapIssues']),
        ];
    }

    public function hasIssues(array $metadata): bool
    {
        foreach ($metadata['analysis_results'] ?? [] as $analysis) {
            if (!empty($analysis['issues'])) {
                return true;
            }
        }
        return false;
    }

    public function mapIssues(array $analysisResults): array
    {
        $issues = [];
        foreach ($analysisResults as $analysis) {
            foreach ($analysis['issues'] as $type => $data) {
                $issues[] = array_merge($data, ['type' => $type]);
            }
        }
        return $issues;
    }
}
