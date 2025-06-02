<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Storage;

class StorageFactory
{
    public function __construct(
        private readonly DatabaseStorage $databaseStorage,
        private readonly FileStorage $fileStorage,
        private readonly array $config
    ) {}

    public function getStorage(): StorageInterface
    {
        return $this->config['storage']['type'] === 'database'
            ? $this->databaseStorage
            : $this->fileStorage;
    }
}
