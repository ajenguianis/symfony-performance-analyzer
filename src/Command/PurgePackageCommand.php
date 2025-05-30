<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'performance-analyzer:purge-package',
    description: 'Remove resources associated with a package'
)]
class PurgePackageCommand extends Command
{
    public function __construct(
        private ParameterBagInterface $parameters,
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('package', null, InputOption::VALUE_REQUIRED, 'The package to purge');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $package = $input->getOption('package');
        $cacheDir = $this->parameters->get('kernel.cache_dir');
        $configFile = $cacheDir . '/cleanup.yaml';

        if (!file_exists($configFile)) {
            $output->writeln('No package resource configuration found.');
            return Command::FAILURE;
        }

        $cleanupConfig = Yaml::parseFile($configFile);

        if (!isset($cleanupConfig[$package])) {
            $output->writeln("No resource configuration found for package: $package");
            return Command::SUCCESS; // Nothing to purge
        }

        $config = $cleanupConfig[$package];

        // Drop database tables
        if (isset($config['tables'])) {
            foreach ($config['tables'] as $table) {
                try {
                    $this->connection->executeStatement("DROP TABLE IF EXISTS $table");
                    $output->writeln("Dropped table: $table");
                } catch (\Exception $e) {
                    $output->writeln("Failed to drop table $table: " . $e->getMessage());
                }
            }
        }

        // Delete files
        if (isset($config['files'])) {
            foreach ($config['files'] as $file) {
                $fullPath = $this->parameters->get('kernel.project_dir') . '/' . $file;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                    $output->writeln("Deleted file: $file");
                }
            }
        }

        // Update cached configuration
        unset($cleanupConfig[$package]);
        file_put_contents($configFile, Yaml::dump($cleanupConfig));
        $output->writeln("Purge completed for package: $package");

        return Command::SUCCESS;
    }
}
