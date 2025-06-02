<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Unit\Controller;

use AA\PerformanceAnalyzer\Controller\DashboardController;
use AA\PerformanceAnalyzer\Service\PerformanceSummary;
use AA\PerformanceAnalyzer\Service\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class DashboardControllerTest extends TestCase
{
    private DashboardController $controller;
    private MockObject $storage;
    private MockObject $summary;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(StorageInterface::class);
        $this->summary = $this->createMock(PerformanceSummary::class);
        $this->controller = new DashboardController($this->storage, $this->summary, []);
    }

    public function testIndex(): void
    {
        $request = Request::create('/_performance');
        $this->storage->method('findRecent')->willReturn([]);
        $this->storage->method('getStatistics')->willReturn([]);
        $this->summary->method('generateSummary')->willReturn(['routes' => []]);

        $response = $this->controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('dashboard.html.twig', $response->getContent());
    }
}
