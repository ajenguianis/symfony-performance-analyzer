<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AA\PerformanceAnalyzer\Analyzer\PerformanceAnalyzer;
use AA\PerformanceAnalyzer\Service\ReportGenerator;
use AA\PerformanceAnalyzer\Service\CopilotRecommendationService;

class AnalyzePerformanceCommand extends Command
{
    protected static $defaultName = 'aa:analyze:performance';

    public function __construct(
        private PerformanceAnalyzer $performanceAnalyzer,
        private ReportGenerator $reportGenerator,
        private ?CopilotRecommendationService $copilotService = null
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Analyze application performance')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output format (json, csv, html)', 'json')
            ->addOption('copilot', null, InputOption::VALUE_NONE, 'Get GitHub Copilot recommendations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = $this->performanceAnalyzer->analyze();
        $format = $input->getOption('output');
        $useCopilot = $input->getOption('copilot');

        $report = $this->reportGenerator->generate($data, $format);

        if ($useCopilot && $this->copilotService) {
            $recommendations = $this->copilotService->getPerformanceRecommendations($data);
            $report .= "\n\nCopilot Recommendations:\n" . json_encode($recommendations, JSON_PRETTY_PRINT);
        }

        $output->writeln($report);

        return Command::SUCCESS;
    }
}
