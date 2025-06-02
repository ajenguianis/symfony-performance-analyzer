<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Formatter;

final readonly class CiBadgeFormatter
{
    public function format(array $stats, string $minSeverity): string
    {
        $color = 'green';
        $status = 'Passing';

        foreach ($stats as $stat) {
            if ($stat['avgResponseTime'] > 500 || $stat['avgMemoryUsage'] > 256 * 1024 * 1024) {
                $color = $minSeverity === 'warning' ? 'orange' : 'red';
                $status = $minSeverity === 'warning' ? 'Warning' : 'Failing';
                break;
            }
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="120" height="20">
    <rect width="120" height="20" fill="{$color}"/>
    <text x="10" y="14" fill="white" font-family="Arial" font-size="12">Performance: {$status}</text>
</svg>
SVG;
    }
}
