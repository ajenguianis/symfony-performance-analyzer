<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AA\PerformanceAnalyzer\Analyzer\PerformanceAnalyzer;
use AA\PerformanceAnalyzer\Analyzer\CodeAnalyzer;
use AA\PerformanceAnalyzer\Service\ReportGenerator;

#[AsCommand(
    name: 'analyze:performance',
    description: 'Analyze application performance and code quality'
)]
class AnalyzePerformanceCommand extends Command
{
    public function __construct(
        private PerformanceAnalyzer $performanceAnalyzer,
        private CodeAnalyzer $codeAnalyzer,
        private ReportGenerator $reportGenerator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output format (json, csv, html)', 'json')
            ->addOption('code-path', null, InputOption::VALUE_OPTIONAL, 'Path to analyze for code issues', 'src');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Performance analysis
        $performanceData = $this->performanceAnalyzer->analyze();

        // Code analysis
        $codePath = $input->getOption('code-path');
        $codeData = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($codePath));
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $codeData[$file->getPathname()] = $this->codeAnalyzer->analyzeFile($file->getPathname());
            }
        }

        $data = [
            'performance' => $performanceData,
            'code_analysis' => $codeData,
        ];

        $report = $this->reportGenerator->generate($data, $input->getOption('output'));
        $output->writeln($report);

        return Command::SUCCESS;
    }
}
