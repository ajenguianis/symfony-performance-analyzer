<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Integration;

use AA\PerformanceAnalyzer\Entity\PerformanceLog;
use AA\PerformanceAnalyzer\Service\PerformanceTracker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PerformanceTrackerIntegrationTest extends KernelTestCase
{
    private PerformanceTracker $tracker;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->tracker = self::getContainer()->get(PerformanceTracker::class);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testFullTrackingWorkflow(): void
    {
        $request = Request::create('/test', 'GET', [], [], [], ['HTTP_X-Request-ID' => 'test123']);
        $request->attributes->set('_controller', 'App\Controller\SimpleController::index');
        $response = new Response();
        $identifier = 'test_' . uniqid();

        $this->tracker->startTracking($identifier);
        $result = $this->tracker->stopTracking($identifier, $request, $response);

        $this->entityManager->flush();

        $log = $this->entityManager->getRepository(PerformanceLog::class)->findOneBy(['requestId' => 'test123']);
        $this->assertNotNull($log);
        $this->assertEquals('test', $log->getRoute());
        $this->assertGreaterThan(0, $log->getResponseTime());
        $this->assertGreaterThan(0, $log->getMemoryUsage());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE performance_log');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE performance_stat');
    }
}
