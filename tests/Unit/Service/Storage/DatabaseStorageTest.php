<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Service\Storage;

use AA\PerformanceAnalyzer\Entity\PerformanceLog;
use AA\PerformanceAnalyzer\Model\PerformanceResult;
use AA\PerformanceAnalyzer\Service\Storage\DatabaseStorage;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DatabaseStorageTest extends TestCase
{
    private DatabaseStorage $storage;
    private MockObject $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->storage = new DatabaseStorage($this->entityManager);
    }

    public function testStore(): void
    {
        $result = new PerformanceResult();
        $result->setResponseTime(100)->setMemoryUsage(1048576);

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(PerformanceLog::class));
        $this->entityManager->expects($this->once())->method('flush');

        $this->storage->store($result);
    }
}
