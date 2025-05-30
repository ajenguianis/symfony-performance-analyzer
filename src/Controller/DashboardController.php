<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Controller;

use AA\PerformanceAnalyzer\Repository\PerformanceAnalysisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/performance-dashboard', name: 'performance_dashboard')]
    public function index(PerformanceAnalysisRepository $repo): Response
    {
        return $this->render('@PerformanceAnalyzer/dashboard.html.twig', [
            'analyses' => $repo->findRecentAnalyses(10),
        ]);
    }
}
