<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Storage;

use AA\PerformanceAnalyzer\Model\PerformanceResult;

interface StorageInterface
{
    public function store(PerformanceResult $result): void;
    public function findByRoute(string $route, int $limit = 100): array;
    public function findRecent(int $limit = 100): array;
    public function getStatistics(): array;
}
