<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Command;

use AA\PerformanceAnalyzer\Service\Formatter\HtmlReportFormatter;
use AA\PerformanceAnalyzer\Service\Storage\DatabaseStorage;
use AA\PerformanceAnalyzer\Service\Storage\StorageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'aa:performance:generate-report',
    description: 'Generate a performance report for the application'
)]
final class GenerateReportCommand extends Command
{
    public function __construct(
        private readonly DatabaseStorage $storage,
        private readonly HtmlReportFormatter $reportFormatter
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('route', 'r', InputOption::VALUE_OPTIONAL, 'Filter by route')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit number of records', 100)
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file path', 'performance-report.html')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run without saving the report');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $route = $input->getOption('route');
        $limit = (int) $input->getOption('limit');
        $outputFile = $input->getOption('output');
        $dryRun = $input->getOption('dry-run');

        $logs = $route
            ? $this->storage->findByRoute($route, $limit)
            : $this->storage->findRecent($limit);

        $stats = $this->storage->getStatistics();
        $report = $this->reportFormatter->format($logs, $stats);

        if (!$dryRun) {
            file_put_contents($outputFile, $report);
            $io->success("Report generated successfully at: {$outputFile}");
        } else {
            $io->section('Dry Run Output:');
            $io->text($report);
        }

        return Command::SUCCESS;
    }
}
