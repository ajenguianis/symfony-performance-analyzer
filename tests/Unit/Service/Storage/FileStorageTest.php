<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Service\Storage;

use AA\PerformanceAnalyzer\Model\PerformanceResult;
use AA\PerformanceAnalyzer\Service\Storage\FileStorage;
use PHPUnit\Framework\TestCase;

final class FileStorageTest extends TestCase
{
    private FileStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new FileStorage(sys_get_temp_dir());
    }

    public function testStore(): void
    {
        $result = new PerformanceResult();
        $result->setResponseTime(100)->setMemoryUsage(1048576);

        $this->storage->store($result);

        $files = glob(sys_get_temp_dir() . '/performance_*.json');
        $this->assertNotEmpty($files);
        $data = json_decode(file_get_contents($files[0]), true);
        $this->assertEquals(100, $data['response_time']);
    }
}
