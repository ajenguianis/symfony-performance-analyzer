<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

class BuildCleanupConfigCommand extends Command
{
    protected static $defaultName = 'performance-analyzer:build-cleanup-config';

    public function __construct(private ParameterBagInterface $parameters)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Build cached configuration for package resource purging');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cleanupConfig = [];
        $packages = $this->parameters->get('kernel.bundles_metadata');

        foreach ($packages as $packageName => $package) {
            $configPath = $package['path'] . '/Resources/config/cleanup.yaml';
            if (file_exists($configPath)) {
                $config = Yaml::parseFile($configPath);
                $cleanupConfig[$packageName] = $config;
            }
        }

        $cacheDir = $this->parameters->get('kernel.cache_dir');
        file_put_contents($cacheDir . '/cleanup.yaml', Yaml::dump($cleanupConfig));
        $output->writeln('Package resource purge configuration cached successfully.');

        return Command::SUCCESS;
    }
}
