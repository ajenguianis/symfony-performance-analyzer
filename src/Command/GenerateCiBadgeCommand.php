<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Command;

use AA\PerformanceAnalyzer\Service\Formatter\CiBadgeFormatter;
use AA\PerformanceAnalyzer\Service\Storage\StorageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'performance:generate-ci-badge',
    description: 'Generate a CI/CD badge based on performance metrics'
)]
final class GenerateCiBadgeCommand extends Command
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly CiBadgeFormatter $badgeFormatter
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file path', 'performance-badge.svg')
            ->addOption('severity', 's', InputOption::VALUE_OPTIONAL, 'Minimum severity to report', 'warning');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $outputFile = $input->getOption('output');
        $severity = $input->getOption('severity');

        $stats = $this->storage->getStatistics();
        $badge = $this->badgeFormatter->format($stats, $severity);

        file_put_contents($outputFile, $badge);
        $io->success("CI badge generated successfully at: {$outputFile}");

        return Command::SUCCESS;
    }
}
