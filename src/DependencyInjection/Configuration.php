<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('aa_performance_analyzer');
        $treeBuilder->getRootNode()
            ->children()
            ->booleanNode('enabled')->defaultTrue()->end()
            ->scalarNode('base_template')->defaultValue('@PerformanceAnalyzer/dashboard.html.twig')->end()
            ->arrayNode('collectors')
            ->children()
            ->booleanNode('database')->defaultTrue()->end()
            ->booleanNode('memory')->defaultTrue()->end()
            ->booleanNode('http')->defaultTrue()->end()
            ->booleanNode('cache')->defaultTrue()->end()
            ->end()
            ->end()
            ->arrayNode('ai_integration')
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->end()
            ->end()
            ->arrayNode('thresholds')
            ->children()
            ->integerNode('slow_query_time')->defaultValue(100)->end()
            ->integerNode('memory_limit_warning')->defaultValue(128)->end()
            ->end()
            ->end()
            ->end();
        return $treeBuilder;
    }
}
