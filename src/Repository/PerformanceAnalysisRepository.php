<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Repository;

use AA\PerformanceAnalyzer\Entity\PerformanceAnalysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PerformanceAnalysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerformanceAnalysis::class);
    }

    public function findRecentAnalyses(int $limit = 50): array
    {
        return $this->createQueryBuilder('pa')
            ->orderBy('pa.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
