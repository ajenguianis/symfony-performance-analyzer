<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class SymfonyPerformanceAnalyzerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $container->setParameter('symfony_performance_analyzer.config', $config);
        $container->setParameter('symfony_performance_analyzer.enabled', $config['enabled']);
        $container->setParameter('symfony_performance_analyzer.thresholds', $config['thresholds']);
        $container->setParameter('symfony_performance_analyzer.analyzers', $config['analyzers']);
        $container->setParameter('symfony_performance_analyzer.storage', $config['storage']);
        $container->setParameter('symfony_performance_analyzer.sampling', $config['sampling']);
        $container->setParameter('symfony_performance_analyzer.circuit_breaker', $config['circuit_breaker']);
        $container->setParameter('symfony_performance_analyzer.dashboard', $config['dashboard']);
        $container->setParameter('symfony_performance_analyzer.profiler', $config['profiler']);
        $container->setParameter('symfony_performance_analyzer.ci_cd', $config['ci_cd']);
    }

    public function getAlias(): string
    {
        return 'symfony_performance_analyzer';
    }
}
