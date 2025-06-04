<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Storage;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class StorageFactory
{
    public function __construct(
        private readonly DatabaseStorage $databaseStorage,
        private readonly FileStorage $fileStorage,
        #[Autowire('%symfony_performance_analyzer.storage%')]
        private readonly array $config
    ) {}

    public function getStorage(): StorageInterface
    {
        return $this->config['type'] === 'database'
            ? $this->databaseStorage
            : $this->fileStorage;
    }
}
