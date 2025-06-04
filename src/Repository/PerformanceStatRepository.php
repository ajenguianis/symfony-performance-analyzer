<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Repository;

use AA\PerformanceAnalyzer\Entity\PerformanceStat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PerformanceStat>
 */
class PerformanceStatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerformanceStat::class);
    }

    public function getGlobalStatistics(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.route, s.totalRequests, s.avgResponseTime, s.avgMemoryUsage, s.avgQueryCount, s.maxResponseTime, s.maxMemoryUsage, s.maxQueryCount')
            ->orderBy('s.avgResponseTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByRoute(string $route): ?PerformanceStat
    {
        return $this->findOneBy(['route' => $route]);
    }
}
