<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Controller;

use AA\PerformanceAnalyzer\Service\PerformanceSummary;
use AA\PerformanceAnalyzer\Service\Storage\StorageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly PerformanceSummary $summary,
        #[Autowire('%symfony_performance_analyzer.dashboard%')]
        private readonly array $dashboardConfig
    ) {}

    #[Route('/_performance', name: 'performance_dashboard')]
    public function index(Request $request): Response
    {
        if ($this->dashboardConfig['security']['enable_firewall']) {
            $allowedIps = $this->dashboardConfig['security']['allowed_ips'] ?? [];
            if (!empty($allowedIps) && !in_array($request->getClientIp(), $allowedIps, true)) {
                throw new AccessDeniedHttpException('Access denied to performance dashboard');
            }
        }

        $logs = $this->storage->findRecent(100);
        $stats = $this->storage->getStatistics();
        $summary = $this->summary->generateSummary();
        return $this->render('@SymfonyPerformanceAnalyzer/dashboard.html.twig', [
            'logs' => $logs,
            'stats' => $stats,
            'summary' => $summary
        ]);
    }
}
