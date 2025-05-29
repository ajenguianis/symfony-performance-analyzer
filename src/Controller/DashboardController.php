<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Controller;

use AA\PerformanceAnalyzer\Service\AnalysisStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private AnalysisStorage $storage
    ) {}

    #[Route('/performance-dashboard', name: 'performance_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('@AAPerformanceAnalyzer/dashboard.html.twig', [
            'recent_analyses' => $this->storage->getLastAnalyses(5),
        ]);
    }

    #[Route('/performance-dashboard/data', name: 'performance_dashboard_data')]
    public function dashboardData(): JsonResponse
    {
        $analyses = $this->storage->getLastAnalyses(30);

        $data = [
            'labels' => [],
            'datasets' => [
                'response_time' => [
                    'label' => 'Temps de réponse (ms)',
                    'data' => [],
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                ],
                'query_count' => [
                    'label' => 'Requêtes SQL',
                    'data' => [],
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                ],
                'memory_usage' => [
                    'label' => 'Mémoire (MB)',
                    'data' => [],
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                ],
            ],
            'nplusone' => [],
        ];

        foreach ($analyses as $analysis) {
            $data['labels'][] = $analysis->getCreatedAt()->format('d/m H:i');
            $data['datasets']['response_time']['data'][] = $analysis->getResponseTime();
            $data['datasets']['query_count']['data'][] = $analysis->getQueryCount();
            $data['datasets']['memory_usage']['data'][] = round($analysis->getMemoryUsage() / 1024 / 1024, 2);

            foreach ($analysis->getNplusOneIssues() as $issue) {
                if (!isset($data['nplusone'][$issue['pattern']])) {
                    $data['nplusone'][$issue['pattern']] = [
                        'pattern' => $issue['pattern'],
                        'count' => 0,
                        'total_time' => 0,
                    ];
                }
                $data['nplusone'][$issue['pattern']]['count'] += $issue['occurrences'];
                $data['nplusone'][$issue['pattern']]['total_time'] += $issue['total_time'];
            }
        }

        // Tri des problèmes N+1 par temps total
        usort($data['nplusone'], fn($a, $b) => $b['total_time'] <=> $a['total_time']);

        return $this->json($data);
    }
}
