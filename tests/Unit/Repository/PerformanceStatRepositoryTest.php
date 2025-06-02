<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Repository;

use AA\PerformanceAnalyzer\Entity\PerformanceStat;
use AA\PerformanceAnalyzer\Repository\PerformanceStatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PerformanceStatRepositoryTest extends TestCase
{
    private PerformanceStatRepository $repository;
    private MockObject $entityManager;
    private MockObject $entityRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturn($this->entityRepository);
        $this->repository = new PerformanceStatRepository($this->entityManager);
    }

    public function testGetStatistics(): void
    {
        $this->entityRepository->method('findAll')->willReturn([new PerformanceStat()]);

        $stats = $this->repository->getStatistics();

        $this->assertCount(1, $stats);
        $this->assertInstanceOf(PerformanceStat::class, $stats[0]);
    }
}