<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Command;

use AA\PerformanceAnalyzer\Command\GenerateCiBadgeCommand;
use AA\PerformanceAnalyzer\Service\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class GenerateCiBadgeCommandTest extends TestCase
{
    private GenerateCiBadgeCommand $command;
    private MockObject $storage;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);
        $this->command = new GenerateCiBadgeCommand($this->storage, []);
    }

    public function testExecuteGeneratesBadge(): void
    {
        $this->storage->method('getStatistics')->willReturn(['avg_response_time' => 100]);

        $input = new ArrayInput(['--output' => sys_get_temp_dir() . '/badge.svg']);
        $output = new NullOutput();

        $code = $this->command->run($input, $output);

        $this->assertEquals(0, $code);
        $this->assertFileExists(sys_get_temp_dir() . '/badge.svg');
    }
}
