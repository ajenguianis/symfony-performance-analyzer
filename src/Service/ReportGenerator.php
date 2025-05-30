<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service;

class ReportGenerator
{
    public function generate(array $data, string $format): string
    {
        return match ($format) {
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            'csv' => $this->toCsv($data),
            'html' => $this->toHtml($data),
            default => throw new \InvalidArgumentException("Unsupported format: $format"),
        };
    }

    private function toCsv(array $data): string
    {
        $output = "Category,Metric,Value\n";
        foreach ($data['performance'] as $key => $value) {
            $output .= "Performance,$key," . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
        foreach ($data['code_analysis'] as $file => $analysis) {
            $output .= "Code,$file,Complexity: {$analysis['cognitive_complexity']}, Issues: " . json_encode($analysis['issues']) . "\n";
        }
        return $output;
    }

    private function toHtml(array $data): string
    {
        $html = "<html><head><script src='/bundles/performanceanalyzer/chart.js'></script><style>table { border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; }</style></head><body>";
        $html .= "<h1>Performance & Code Analysis Report</h1>";

        // Performance Chart
        $html .= "<h2>Performance Metrics</h2><canvas id='performanceChart'></canvas><script>";
        $html .= "new Chart(document.getElementById('performanceChart'), { 
            type: 'bar', 
            data: { 
                labels: ['Queries', 'Memory'], 
                datasets: [{ 
                    label: 'Performance', 
                    data: [" . ($data['performance']['database']['total_queries'] ?? 0) . "," . ($data['performance']['memory']['peak_usage'] / 1024 / 1024) . "],
                    backgroundColor: ['rgba(54, 162, 235, 0.2)', 'rgba(255, 99, 132, 0.2)'],
                    borderColor: ['rgba(54, 162, 235, 1)', 'rgba(255, 99, 132, 1)'],
                    borderWidth: 1
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });";
        $html .= "</script>";

        // Code Analysis Table
        $html .= "<h2>Code Analysis</h2><table><tr><th>File</th><th>Cognitive Complexity</th><th>Issues</th></tr>";
        foreach ($data['code_analysis'] as $file => $analysis) {
            $issues = implode('<br>', array_map(fn($i) => $i['message'] . ' (Line: ' . $i['line'] . ')', $analysis['issues']));
            $html .= "<tr><td>$file</td><td>{$analysis['cognitive_complexity']}</td><td>$issues</td></tr>";
        }
        $html .= "</table></body></html>";

        return $html;
    }
}
