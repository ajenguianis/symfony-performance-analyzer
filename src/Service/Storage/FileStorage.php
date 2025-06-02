<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Storage;

use AA\PerformanceAnalyzer\Model\PerformanceResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Stores performance data in JSON files.
 */
final class FileStorage implements StorageInterface
{
    private array $config;

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/performance')]
        private readonly string $storagePath,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger,
        array $config = []
    ) {
        $this->config = $config;
    }

    /**
     * Stores a performance result as a JSON file.
     *
     * @param PerformanceResult $result Performance metrics to store
     * @throws \RuntimeException If storage fails
     */
    public function store(PerformanceResult $result): void
    {
        try {
            $this->ensureDirectoryExists();

            $data = [
                'timestamp' => time(),
                'route' => $result->getRoute(),
                'method' => $result->getMethod(),
                'response_time' => $result->getResponseTime(),
                'memory_usage' => $result->getMemoryUsage(),
                'status_code' => $result->getStatusCode(),
                'cognitive_complexity' => $result->getCognitiveComplexity(),
                'collector_data' => $result->getCollectorData(),
                'analysis_results' => $this->serializeAnalysisResults($result->getAnalysisResults()),
                'metadata' => $result->getMetadata()
            ];

            $filename = sprintf(
                '%s/%s_%s.json',
                $this->storagePath,
                date('Y-m-d_H-i-s'),
                uniqid()
            );

            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));

            $this->enforceRetentionPolicy();
        } catch (\Exception $e) {
            $this->logger->error('Failed to store performance data in file', [
                'exception' => $e->getMessage(),
                'filename' => $filename ?? 'unknown'
            ]);
            throw new \RuntimeException('File storage failed: ' . $e->getMessage());
        }
    }

    /**
     * Retrieves performance data matching criteria.
     *
     * @param array $criteria Filter criteria
     * @return array Array of performance data
     */
    public function findByRoute(string $route, int $limit = 100): array
    {
        return $this->retrieve(['route' => $route], $limit);
    }

    /**
     * Retrieves recent performance data.
     *
     * @param int $limit Maximum number of records
     * @return array Array of recent data
     */
    public function findRecent(int $limit = 100): array
    {
        return $this->retrieve([], $limit);
    }

    /**
     * Gets aggregated statistics.
     *
     * @return array Array of statistics
     */
    public function getStatistics(): array
    {
        try {
            $files = glob($this->storagePath . '/*.json');
            $stats = [];
            $routes = [];

            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                $route = $data['route'] ?? 'unknown';

                if (!isset($routes[$route])) {
                    $routes[$route] = [
                        'totalRequests' => 0,
                        'responseTimes' => [],
                        'memoryUsages' => [],
                        'queryCounts' => []
                    ];
                }

                $routes[$route]['totalRequests']++;
                $routes[$route]['responseTimes'][] = $data['response_time'] ?? 0;
                $routes[$route]['memoryUsages'][] = $data['memory_usage'] ?? 0;
                $routes[$route]['queryCounts'][] = count($data['collector_data']['database_queries'] ?? []);

                foreach ($routes as $route => $routeData) {
                    $stats[] = [
                        'route' => $route,
                        'totalRequests' => $routeData['totalRequests'],
                        'avgResponseTime' => array_sum($routeData['responseTimes']) / $routeData['totalRequests'],
                        'avgMemoryUsage' => array_sum($routeData['memoryUsages']) / $routeData['totalRequests'],
                        'avgQueryCount' => array_sum($routeData['queryCounts']) / $routeData['totalRequests'],
                        'maxResponseTime' => max($routeData['responseTimes']),
                        'maxMemoryUsage' => max($routeData['memoryUsages']),
                        'maxQueryCount' => max($routeData['queryCounts'])
                    ];
                }
            }

            return $stats;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve file storage statistics', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Retrieves data matching criteria with limit.
     *
     * @param array $criteria Filter criteria
     * @param int $limit Maximum number of records
     * @return array Array of matching data
     */
    private function retrieve(array $criteria = [], int $limit = 100): array
    {
        try {
            $this->ensureDirectoryExists();

            $files = glob($this->storagePath . '/*.json');
            $results = [];

            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($this->matchesCriteria($data, $criteria)) {
                    $results[] = $data;
                }
            }

            usort($results, fn($a, $b) => $b['timestamp'] - $a['timestamp']);
            return array_slice($results, 0, $limit);
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve file storage data', [
                'exception' => $e->getMessage(),
                'criteria' => $criteria
            ]);
            return [];
        }
    }

    /**
     * Cleans up old data based on retention policy.
     *
     * @param \DateTimeInterface $before Cutoff date
     * @return int Number of deleted files
     */
    public function cleanup(\DateTimeInterface $before): int
    {
        try {
            $this->ensureDirectoryExists();

            $files = glob($this->storagePath . '/*.json');
            $deleted = 0;
            $beforeTimestamp = $before->getTimestamp();

            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if (isset($data['timestamp']) && $data['timestamp'] < $beforeTimestamp) {
                    unlink($file);
                    $deleted++;
                }
            }

            return $deleted;
        } catch (\Exception $e) {
            $this->logger->error('Failed to clean up file storage', [
                'exception' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Enforces retention policies for file storage.
     */
    private function enforceRetentionPolicy(): void
    {
        try {
            $retentionDays = $this->config['storage']['retention']['days'] ?? 30;
            $maxRecords = $this->config['storage']['retention']['max_records'] ?? 10000;

            // Delete old files
            $cutoff = new \DateTimeImmutable("-{$retentionDays} days");
            $this->cleanup($cutoff);

            // Limit total files
            $files = glob($this->storagePath . '/*.json');
            if (count($files) > $maxRecords) {
                usort($files, fn($a, $b) => filemtime($a) - filemtime($b));
                $excess = array_slice($files, 0, count($files) - $maxRecords);
                foreach ($excess as $file) {
                    unlink($file);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to enforce file retention policy', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    private function ensureDirectoryExists(): void
    {
        if (!$this->filesystem->exists($this->storagePath)) {
            $this->filesystem->mkdir($this->storagePath, 0755);
        }
    }

    private function serializeAnalysisResults(array $results): array
    {
        $serialized = [];
        foreach ($results as $analyzerName => $result) {
            $serialized[$analyzerName] = [
                'issues' => $result->getIssues(),
                'suggestions' => $result->getSuggestions(),
                'metrics' => $result->getMetrics()
            ];
        }
        return $serialized;
    }

    private function matchesCriteria(array $data, array $criteria): bool
    {
        foreach ($criteria as $key => $value) {
            if (!isset($data[$key]) || $data[$key] !== $value) {
                return false;
            }
        }
        return true;
    }
}
