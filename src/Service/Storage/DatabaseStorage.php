<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service\Storage;

use AA\PerformanceAnalyzer\Entity\PerformanceLog;
use AA\PerformanceAnalyzer\Entity\PerformanceStat;
use AA\PerformanceAnalyzer\Model\PerformanceResult;
use AA\PerformanceAnalyzer\Repository\PerformanceLogRepository;
use AA\PerformanceAnalyzer\Repository\PerformanceStatRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Stores performance data in a database using Doctrine ORM.
 */
final class DatabaseStorage implements StorageInterface
{
    private array $config;
    private PerformanceLogRepository $performanceLogRepository;
    private PerformanceStatRepository $performanceStatRepository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        array $config = []
    ) {
        $this->config = $config;
        $this->performanceLogRepository = $entityManager->getRepository(PerformanceLog::class);
        $this->performanceStatRepository = $entityManager->getRepository(PerformanceStat::class);
    }

    /**
     * Stores a performance result in the database.
     *
     * @param PerformanceResult $result Performance metrics to store
     * @throws \RuntimeException If storage fails
     */
    public function store(PerformanceResult $result): void
    {
        try {
            $log = new PerformanceLog();
            $log->setRoute($result->getRoute())
                ->setMethod($result->getMethod())
                ->setResponseTime($result->getResponseTime())
                ->setMemoryUsage($result->getMemoryUsage())
                ->setStatusCode($result->getStatusCode())
                ->setCognitiveComplexity($result->getCognitiveComplexity());

            $metadata = [
                'collector_data' => $result->getCollectorData(),
                'analysis_results' => array_map(
                    fn($analysisResult) => [
                        'issues' => $analysisResult->getIssues(),
                        'suggestions' => $analysisResult->getSuggestions(),
                        'metrics' => $analysisResult->getMetrics()
                    ],
                    $result->getAnalysisResults()
                ),
                'custom_metadata' => $result->getMetadata()
            ];

            $log->setMetadata($metadata);

            $queries = $result->getCollectorData('database_queries', []);
            $log->setQueryCount(count($queries));
            $log->setQueryTime(array_sum(array_column($queries, 'duration')));

            $this->entityManager->persist($log);
            $this->updateStatistics($result);
            $this->entityManager->flush();

            $this->enforceRetentionPolicy();
        } catch (DBALException $e) {
            $this->logger->error('Failed to store performance data', [
                'exception' => $e->getMessage(),
                'route' => $result->getRoute()
            ]);
            throw new \RuntimeException('Database storage failed: ' . $e->getMessage());
        }
    }

    /**
     * Finds performance logs by route.
     *
     * @param string $route Route to filter by
     * @param int $limit Maximum number of records
     * @return PerformanceLog[] Array of performance logs
     */
    public function findByRoute(string $route, int $limit = 100): array
    {
        try {
            return $this->performanceLogRepository->findByRoute($route, $limit);
        } catch (DBALException $e) {
            $this->logger->error('Failed to retrieve logs by route', [
                'route' => $route,
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Finds recent performance logs.
     *
     * @param int $limit Maximum number of records
     * @return PerformanceLog[] Array of recent logs
     */
    public function findRecent(int $limit = 100): array
    {
        try {
            return $this->performanceLogRepository->findRecent($limit);
        } catch (DBALException $e) {
            $this->logger->error('Failed to retrieve recent logs', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Gets global performance statistics.
     *
     * @return array Array of statistics
     */
    public function getStatistics(): array
    {
        try {
            return $this->performanceStatRepository->getGlobalStatistics();
        } catch (DBALException $e) {
            $this->logger->error('Failed to retrieve statistics', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Updates performance statistics for a route.
     *
     * @param PerformanceResult $result Performance result to update stats with
     */
    private function updateStatistics(PerformanceResult $result): void
    {
        try {
            $stat = $this->performanceStatRepository->findByRoute($result->getRoute());

            if (!$stat) {
                $stat = new PerformanceStat();
                $stat->setRoute($result->getRoute());
            }

            $totalRequests = $stat->getTotalRequests();
            $newTotalRequests = $totalRequests + 1;

            $newAvgResponseTime = (($stat->getAvgResponseTime() * $totalRequests) + $result->getResponseTime()) / $newTotalRequests;
            $newAvgMemoryUsage = (($stat->getAvgMemoryUsage() * $totalRequests) + $result->getMemoryUsage()) / $newTotalRequests;

            $queries = $result->getCollectorData('database_queries', []);
            $queryCount = count($queries);
            $newAvgQueryCount = (($stat->getAvgQueryCount() * $totalRequests) + $queryCount) / $newTotalRequests;

            $stat->setTotalRequests($newTotalRequests)
                ->setAvgResponseTime($newAvgResponseTime)
                ->setAvgMemoryUsage($newAvgMemoryUsage)
                ->setAvgQueryCount($newAvgQueryCount)
                ->setMaxResponseTime(max($stat->getMaxResponseTime(), $result->getResponseTime()))
                ->setMaxMemoryUsage(max($stat->getMaxMemoryUsage(), $result->getMemoryUsage()))
                ->setMaxQueryCount(max($stat->getMaxQueryCount(), $queryCount));

            $this->entityManager->persist($stat);
        } catch (DBALException $e) {
            $this->logger->error('Failed to update statistics', [
                'route' => $result->getRoute(),
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enforces data retention policies.
     */
    private function enforceRetentionPolicy(): void
    {
        try {
            $retentionDays = $this->config['storage']['retention']['days'] ?? 30;
            $maxRecords = $this->config['storage']['retention']['max_records'] ?? 10000;

            // Delete old records
            $cutoffDate = new \DateTimeImmutable("-{$retentionDays} days");
            $qb = $this->performanceLogRepository->createQueryBuilder('p');
            $qb->delete()
                ->where('p.createdAt < :cutoff')
                ->setParameter('cutoff', $cutoffDate)
                ->getQuery()
                ->execute();

            // Limit total records
            $count = $this->performanceLogRepository->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->getQuery()
                ->getSingleScalarResult();

            if ($count > $maxRecords) {
                $excess = $count - $maxRecords;
                $qb = $this->performanceLogRepository->createQueryBuilder('p');
                $qb->delete()
                    ->orderBy('p.createdAt', 'ASC')
                    ->setMaxResults($excess)
                    ->getQuery()
                    ->execute();
            }
        } catch (DBALException $e) {
            $this->logger->error('Failed to enforce retention policy', [
                'exception' => $e->getMessage()
            ]);
        }
    }
}
