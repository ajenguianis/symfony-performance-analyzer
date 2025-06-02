<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\DependencyInjection\Compiler;

use AA\PerformanceAnalyzer\Service\PerformanceTracker;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class AnalyzerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(PerformanceTracker::class)) {
            return;
        }

        $definition = $container->findDefinition(PerformanceTracker::class);
        $taggedServices = $container->findTaggedServiceIds('performance.analyzer');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addAnalyzer', [new Reference($id)]);
        }

        $collectorServices = $container->findTaggedServiceIds('performance.collector');
        foreach ($collectorServices as $id => $tags) {
            $definition->addMethodCall('addCollector', [new Reference($id)]);
        }
    }
}
