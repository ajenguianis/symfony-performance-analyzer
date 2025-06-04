<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Command;

use AA\PerformanceAnalyzer\Model\PerformanceResult;
use AA\PerformanceAnalyzer\Service\Analyzer\AnalyzerInterface;
use AA\PerformanceAnalyzer\Service\PerformanceTracker;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[AsCommand(
    name: 'aa:performance:check-quality',
    description: 'Check application performance and code quality in real-time'
)]
final class AnalyzeQualityCommand extends Command
{
    private const MAX_ROUTES = 50;
    private const BATCH_SIZE = 10;
    private const N1_THRESHOLD = 5; // Number of similar queries to consider N+1
    private const COGNITIVE_COMPLEXITY_THRESHOLD = 15;

    public function __construct(
        private readonly PerformanceTracker $performanceTracker,
        private readonly iterable $analyzers,
        private readonly ContainerInterface $container,
        private readonly RouterInterface $router,
        private readonly string $baseUrl,
        private readonly ?string $projectDir = null
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('route', 'r', InputOption::VALUE_OPTIONAL, 'HTTP route to analyze (e.g., /api/users)')
            ->addOption('command', 'c', InputOption::VALUE_OPTIONAL, 'Console command to analyze (e.g., app:process)')
            ->addOption('severity', 's', InputOption::VALUE_OPTIONAL, 'Minimum severity to report (error, warning, notice)', 'error')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file path for JSON report', 'quality-report.json')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate analysis without execution')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format (console, json)', 'console')
            ->addOption('max-routes', null, InputOption::VALUE_OPTIONAL, 'Maximum number of routes to analyze', self::MAX_ROUTES)
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, 'Number of routes to process at once', self::BATCH_SIZE)
            ->addOption('check-n1', null, InputOption::VALUE_NONE, 'Enable N+1 query detection')
            ->addOption('check-complexity', null, InputOption::VALUE_NONE, 'Enable cognitive complexity analysis');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '-1');
        $io = new SymfonyStyle($input, $output);
        $route = $input->getOption('route');
        $commandName = $input->getOption('command');
        $severity = $input->getOption('severity');
        $outputFile = $input->getOption('output');
        $dryRun = $input->getOption('dry-run');
        $format = $input->getOption('format');
        $maxRoutes = (int) $input->getOption('max-routes');
        $batchSize = (int) $input->getOption('batch-size');
        $checkN1 = $input->getOption('check-n1');
        $checkComplexity = $input->getOption('check-complexity');

        if ($route && $commandName) {
            $io->error('Cannot analyze both a route and a command simultaneously.');
            return Command::FAILURE;
        }

        $issues = [];

        if ($dryRun) {
            $io->note('Dry-run mode: Simulating analysis without execution.');
            $routes = $route ? [$route] : $this->getAccessibleGetRoutes();
            if ($commandName) {
                $io->section('Simulated Command Analysis');
                $io->text("Command: $commandName");
            } else {
                $io->section('Simulated Route Analysis');
                $routes = array_slice($routes, 0, $maxRoutes);
                $io->listing($routes);
                $io->note(sprintf('Limited to %d routes in dry-run mode.', count($routes)));
            }
        } else {
            if ($commandName) {
                $issues = $this->analyzeCommand($commandName, $input);
            } else {
                $routes = $route ? [$route] : $this->getAccessibleGetRoutes();
                $routes = array_slice($routes, 0, $maxRoutes);

                $io->note(sprintf('Analyzing %d routes in batches of %d.', count($routes), $batchSize));

                foreach (array_chunk($routes, $batchSize) as $batch) {
                    $batchIssues = $this->processRouteBatch($batch, $io, $checkN1, $checkComplexity);
                    $issues = array_merge($issues, $batchIssues);

                    unset($batchIssues);
                    gc_collect_cycles();
                }
            }
        }

        $issues = array_filter($issues, fn($issue) => $this->isSeveritySufficient($issue['severity'], $severity));

        return $this->outputResults($issues, $io, $format, $outputFile, $dryRun);
    }

    private function processRouteBatch(array $routes, SymfonyStyle $io, bool $checkN1, bool $checkComplexity): array
    {
        $issues = [];

        foreach ($routes as $routePath) {
            $io->text(sprintf('Analyzing route: %s (Memory: %s MB)', $routePath, round(memory_get_usage() / 1024 / 1024, 2)));
            $routeIssues = $this->analyzeRoute($routePath, $checkN1, $checkComplexity);
            $issues = array_merge($issues, $routeIssues);

            unset($routeIssues);
            gc_collect_cycles();
        }

        return $issues;
    }

    private function analyzeRoute(string $route, bool $checkN1, bool $checkComplexity): array
    {
        $issues = [];
        $identifier = 'route_' . $route . '_' . uniqid();
        $this->performanceTracker->startTracking($identifier);

        try {
            $client = HttpClient::create(['timeout' => 10]);
            $response = $client->request('GET', $this->baseUrl . $route);
            $request = Request::create($route);
            $httpResponse = new Response(
                $response->getContent(false),
                $response->getStatusCode(),
                $response->getHeaders(false)
            );

            $result = $this->performanceTracker->stopTracking($identifier, $request, $httpResponse);

            // Basic HTTP checks
            if ($httpResponse->getStatusCode() >= 400) {
                $issues[] = [
                    'severity' => $httpResponse->getStatusCode() >= 500 ? 'error' : 'warning',
                    'message' => sprintf('HTTP %d error detected', $httpResponse->getStatusCode()),
                    'source' => $route,
                ];
            }

            // Performance check
            if ($result->getResponseTime() > 2000) {
                $issues[] = [
                    'severity' => 'warning',
                    'message' => sprintf('Slow response detected (%d ms)', $result->getResponseTime()),
                    'source' => $route,
                ];
            }

            // N+1 query detection
            if ($checkN1 && $this->detectN1Queries($result)) {
                $issues[] = [
                    'severity' => 'warning',
                    'message' => 'Potential N+1 query problem detected',
                    'source' => $route,
                    'details' => ['query_count' => count($result->getQueries())]
                ];
            }

            // Cognitive complexity analysis
            if ($checkComplexity && $this->projectDir) {
                $complexityIssues = $this->analyzeCognitiveComplexity($route);
                $issues = array_merge($issues, $complexityIssues);
            }

            // Run other analyzers
            foreach ($this->analyzers as $analyzer) {
                try {
                    $analysisResult = $analyzer->analyze($request, $httpResponse, $result);
                    foreach ($analysisResult->getIssues() as $type => $issue) {
                        $issues[] = [
                            'severity' => $issue['severity'],
                            'message' => $issue['message'],
                            'source' => $route,
                            'details' => array_diff_key($issue, ['message' => 1, 'severity' => 1]),
                        ];
                    }
                } catch (\Exception $e) {
                    $issues[] = [
                        'severity' => 'error',
                        'message' => sprintf('Analyzer failed: %s', $e->getMessage()),
                        'source' => $route,
                    ];
                }
            }
        } catch (\Exception $e) {
            $issues[] = [
                'severity' => 'error',
                'message' => sprintf('Failed to analyze route: %s', $e->getMessage()),
                'source' => $route,
            ];
        }

        return $issues;
    }

    private function detectN1Queries(PerformanceResult $result): bool
    {
        $queries = $result->getQueries();
        if (count($queries) < self::N1_THRESHOLD) {
            return false;
        }

        // Group similar queries
        $queryGroups = [];
        foreach ($queries as $query) {
            $normalized = preg_replace('/\s+/', ' ', trim($query['sql']));
            $queryGroups[$normalized][] = $query;
        }

        // Check for N+1 patterns
        foreach ($queryGroups as $queries) {
            if (count($queries) >= self::N1_THRESHOLD) {
                return true;
            }
        }

        return false;
    }

    private function analyzeCognitiveComplexity(string $route): array
    {
        $issues = [];

        try {
            // This is a simplified example - you'd need to integrate with a proper complexity analyzer
            $controllerInfo = $this->getControllerInfo($route);
            if (!$controllerInfo) {
                return [];
            }

            $filePath = $this->projectDir . '/src/' . str_replace('\\', '/', $controllerInfo['class']) . '.php';
            if (!file_exists($filePath)) {
                return [];
            }

            // Simplified complexity check - in real implementation you'd use a library
            $content = file_get_contents($filePath);
            $methodContent = $this->extractMethodContent($content, $controllerInfo['method']);

            $complexityScore = $this->calculateComplexity($methodContent);
            if ($complexityScore > self::COGNITIVE_COMPLEXITY_THRESHOLD) {
                $issues[] = [
                    'severity' => 'warning',
                    'message' => sprintf('High cognitive complexity detected (%d)', $complexityScore),
                    'source' => $route,
                    'details' => [
                        'controller' => $controllerInfo['class'],
                        'method' => $controllerInfo['method'],
                        'threshold' => self::COGNITIVE_COMPLEXITY_THRESHOLD
                    ]
                ];
            }
        } catch (\Exception $e) {
            // Silently fail complexity analysis
        }

        return $issues;
    }

    private function getControllerInfo(string $route): ?array
    {
        $routeCollection = $this->router->getRouteCollection();
        $routeObject = $routeCollection->get($route);

        if (!$routeObject) {
            return null;
        }

        $controller = $routeObject->getDefault('_controller');
        if (is_string($controller)) {
            if (str_contains($controller, '::')) {
                [$class, $method] = explode('::', $controller, 2);
                return ['class' => $class, 'method' => $method];
            }
        }

        return null;
    }

    private function extractMethodContent(string $fileContent, string $methodName): string
    {
        if (preg_match('/function\s+' . $methodName . '\s*\([^)]*\)\s*{(.*?)}/s', $fileContent, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private function calculateComplexity(string $methodContent): int
    {
        // Simplified complexity calculation - in real implementation use a proper library
        $score = 0;

        // Count control structures
        $score += substr_count($methodContent, 'if(');
        $score += substr_count($methodContent, 'if (');
        $score += substr_count($methodContent, 'elseif(');
        $score += substr_count($methodContent, 'elseif (');
        $score += substr_count($methodContent, 'else');
        $score += substr_count($methodContent, 'for(');
        $score += substr_count($methodContent, 'for (');
        $score += substr_count($methodContent, 'foreach(');
        $score += substr_count($methodContent, 'foreach (');
        $score += substr_count($methodContent, 'while(');
        $score += substr_count($methodContent, 'while (');
        $score += substr_count($methodContent, 'case ');
        $score += substr_count($methodContent, 'default:');

        // Nested structures increase complexity more
        $score += preg_match_all('/}\s*(else|elseif|catch|finally)\s*{/', $methodContent);

        return $score;
    }

    private function analyzeCommand(string $commandName, InputInterface $input): array
    {
        $issues = [];

        $command = $this->container->get('console.command_loader')->get($commandName);
        if (!$command) {
            return [[
                'severity' => 'error',
                'message' => sprintf("Command '%s' not found.", $commandName),
                'source' => 'console',
            ]];
        }

        $result = $this->performanceTracker->trackCommand($commandName, function () use ($command, $input) {
            return $command->run($input, new \Symfony\Component\Console\Output\NullOutput());
        });

        if ($result->getResponseTime() > 2000) {
            $issues[] = [
                'severity' => 'warning',
                'message' => sprintf('Slow execution detected (%d ms)', $result->getResponseTime()),
                'source' => "command:$commandName",
            ];
        }

        $request = Request::create('/');
        $response = new Response();
        foreach ($this->analyzers as $analyzer) {
            try {
                $analysisResult = $analyzer->analyze($request, $response, $result);
                foreach ($analysisResult->getIssues() as $type => $issue) {
                    $issues[] = [
                        'severity' => $issue['severity'],
                        'message' => $issue['message'],
                        'source' => "command:$commandName",
                        'details' => array_diff_key($issue, ['message' => 1, 'severity' => 1]),
                    ];
                }
            } catch (\Exception $e) {
                $issues[] = [
                    'severity' => 'error',
                    'message' => sprintf('Analyzer failed: %s', $e->getMessage()),
                    'source' => "command:$commandName",
                ];
            }
        }

        return $issues;
    }

    private function getAccessibleGetRoutes(): array
    {
        $routes = [];
        $routeCollection = $this->router->getRouteCollection();

        foreach ($routeCollection->all() as $name => $route) {
            if (in_array('GET', $route->getMethods(), true) || empty($route->getMethods())) {
                $path = $route->getPath();
                if (!preg_match('/{[^}]+}/', $path) && !str_contains($path, '/admin/')) {
                    $routes[] = $path;
                }
            }
        }

        return array_unique($routes);
    }

    private function outputResults(array $issues, SymfonyStyle $io, string $format, string $outputFile, bool $dryRun): int
    {
        $result = [
            'status' => empty($issues) ? 'success' : 'failure',
            'issues' => array_values($issues),
            'summary' => [
                'issue_count' => count($issues),
                'errors' => count(array_filter($issues, fn($i) => $i['severity'] === 'error')),
                'warnings' => count(array_filter($issues, fn($i) => $i['severity'] === 'warning')),
            ],
        ];

        if ($format === 'json') {
            $jsonOutput = json_encode($result, JSON_PRETTY_PRINT);
            if (!$dryRun) {
                file_put_contents($outputFile, $jsonOutput);
                $io->success("Quality report saved to: $outputFile");
            } else {
                $io->section('Dry Run JSON Output:');
                $io->text($jsonOutput);
            }
        } else {
            if (empty($issues)) {
                $io->success('No issues detected! ✅');
            } else {
                $io->section('Issues Detected:');
                foreach ($issues as $issue) {
                    $io->writeln(sprintf(
                        '[%s] %s (Source: %s)',
                        strtoupper($issue['severity']),
                        $issue['message'],
                        $issue['source']
                    ));
                    if (!empty($issue['details'])) {
                        $io->writeln('  Details: ' . json_encode($issue['details']));
                    }
                }
                $io->note(sprintf(
                    'Summary: %d errors, %d warnings',
                    $result['summary']['errors'],
                    $result['summary']['warnings']
                ));
            }
        }

        return empty($issues) ? Command::SUCCESS : Command::FAILURE;
    }

    private function isSeveritySufficient(string $issueSeverity, string $minSeverity): bool
    {
        $levels = ['error' => 3, 'warning' => 2, 'notice' => 1];
        return ($levels[$issueSeverity] ?? 1) >= ($levels[$minSeverity] ?? 2);
    }
}
