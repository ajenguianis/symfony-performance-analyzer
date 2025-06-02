<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer;

use AA\PerformanceAnalyzer\DependencyInjection\Compiler\AnalyzerPass;
use AA\PerformanceAnalyzer\DependencyInjection\SymfonyPerformanceAnalyzerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PerformanceAnalyzerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AnalyzerPass());
    }

    public function getContainerExtension(): SymfonyPerformanceAnalyzerExtension
    {
        return new SymfonyPerformanceAnalyzerExtension();
    }
}
