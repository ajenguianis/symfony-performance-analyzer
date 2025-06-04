<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Repository;

use AA\PerformanceAnalyzer\Entity\PerformanceLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PerformanceLog>
 */
class PerformanceLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerformanceLog::class);
    }

    public function findByRoute(string $route, int $limit = 100): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.route = :route')
            ->setParameter('route', $route)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findSlowest(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.responseTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findBySeverity(string $severity, int $limit = 100): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.metadata LIKE :severity')
            ->setParameter('severity', '%"severity":"' . $severity . '"%')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
