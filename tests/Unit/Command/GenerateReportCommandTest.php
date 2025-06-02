<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Command;

use AA\PerformanceAnalyzer\Command\GenerateReportCommand;
use AA\PerformanceAnalyzer\Service\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class GenerateReportCommandTest extends TestCase
{
    private GenerateReportCommand $command;
    private MockObject $storage;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);
        $this->command = new GenerateReportCommand($this->storage, [], []);
    }

    public function testExecuteGeneratesReport(): void
    {
        $this->storage->method('findRecent')->willReturn([]);
        $this->storage->method('getStatistics')->willReturn([]);

        $input = new ArrayInput(['--format' => 'html', '--output' => sys_get_temp_dir() . '/report.html']);
        $output = new NullOutput();

        $code = $this->command->run($input, $output);

        $this->assertEquals(0, $code);
        $this->assertFileExists(sys_get_temp_dir() . '/report.html');
    }
}
