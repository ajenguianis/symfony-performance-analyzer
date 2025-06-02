<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Repository;

use AA\PerformanceAnalyzer\Entity\PerformanceLog;
use AA\PerformanceAnalyzer\Repository\PerformanceLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PerformanceLogRepositoryTest extends TestCase
{
    private PerformanceLogRepository $repository;
    private MockObject $entityManager;
    private MockObject $entityRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturn($this->entityRepository);
        $this->repository = new PerformanceLogRepository($this->entityManager);
    }

    public function testFindRecent(): void
    {
        $this->entityRepository->method('findBy')->willReturn([new PerformanceLog()]);

        $logs = $this->repository->findRecent(10);

        $this->assertCount(1, $logs);
        $this->assertInstanceOf(PerformanceLog::class, $logs[0]);
    }
}
