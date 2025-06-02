<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Service;

use AA\PerformanceAnalyzer\Model\PerformanceResult;
use AA\PerformanceAnalyzer\Service\PerformanceTracker;
use AA\PerformanceAnalyzer\Service\Storage\StorageInterface;
use AA\PerformanceAnalyzer\Utils\Timer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PerformanceTrackerTest extends TestCase
{
    private PerformanceTracker $tracker;
    private MockObject $storage;
    private MockObject $timer;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);
        $this->timer = $this->createMock(Timer::class);
        $this->tracker = new PerformanceTracker($this->storage, $this->timer, ['max_response_time_ms' => 500], true);
    }

    public function testStartAndStopTracking(): void
    {
        $request = Request::create('/test', 'GET');
        $response = new Response();
        $identifier = 'test_identifier';

        $this->timer->expects($this->once())->method('start')->with($identifier);
        $this->timer->expects($this->once())->method('stop')->with($identifier)->willReturn(0.1);
        $this->storage->expects($this->once())->method('store')->with($this->isInstanceOf(PerformanceResult::class));

        $this->tracker->startTracking($identifier);
        $result = $this->tracker->stopTracking($identifier, $request, $response);

        $this->assertInstanceOf(PerformanceResult::class, $result);
        $this->assertEquals(100, $result->getResponseTime());
        $this->assertEquals('test', $result->getRoute());
    }

    public function testTrackCommand(): void
    {
        $commandName = 'test:command';
        $identifier = 'command_test:command_';
        $callback = fn() => 'result';

        $this->timer->expects($this->once())->method('start');
        $this->timer->expects($this->once())->method('stop')->willReturn(0.2);
        $this->storage->expects($this->once())->method('store');
        $this->timer->expects($this->once())->method('reset');

        $result = $this->tracker->trackCommand($commandName, $callback);

        $this->assertEquals('result', $result);
    }
}
