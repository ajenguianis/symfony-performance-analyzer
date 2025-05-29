<?php

namespace AA\PerformanceAnalyzer\Service;

use AA\PerformanceAnalyzer\Entity\PerformanceAnalysis;
use Doctrine\ORM\EntityManagerInterface;

class AnalysisStorage
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function saveAnalysis(array $data): void
    {
        $analysis = new PerformanceAnalysis();
        $analysis->setData($data);

        $this->em->persist($analysis);
        $this->em->flush();
    }

    public function getLastAnalyses(int $limit): array
    {
        return $this->em->getRepository(PerformanceAnalysis::class)
            ->findBy([], ['createdAt' => 'DESC'], $limit);
    }
}
