<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Formatter;

use AA\PerformanceAnalyzer\Entity\PerformanceLog;
use Twig\Environment;

final class HtmlReportFormatter
{
    public function __construct(
        private readonly Environment $twig
    ) {}

    public function format(array $logs, array $stats): string
    {
        return $this->twig->render(
            '@SymfonyPerformanceAnalyzer/report.html.twig',
            [
                'logs' => $logs,
                'stats' => $stats,
            ]
        );
    }
}
