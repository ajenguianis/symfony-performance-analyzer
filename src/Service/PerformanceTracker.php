<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Service;

use AA\PerformanceAnalyzer\Entity\PerformanceLog;
use AA\PerformanceAnalyzer\Model\PerformanceResult;
use AA\PerformanceAnalyzer\Service\Analyzer\AnalyzerInterface;
use AA\PerformanceAnalyzer\Service\Collector\CollectorInterface;
use AA\PerformanceAnalyzer\Service\Storage\StorageInterface;
use AA\PerformanceAnalyzer\Utils\Timer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tracks performance metrics for HTTP requests and CLI commands.
 */
final class PerformanceTracker
{
    /** @var AnalyzerInterface[] */
    private array $analyzers = [];

    /** @var CollectorInterface[] */
    private array $collectors = [];

    private array $startTimes = [];
    private array $thresholds;
    private bool $enabled;
    private float $samplingRate;
    private bool $degradedMode;
    private int $degradedThresholdMs;

    /**
     * @param StorageInterface $storage Storage for performance data
     * @param Timer $timer Timer for measuring execution time
     * @param array $config Bundle configuration
     * @param bool $enabled Whether tracking is enabled
     */
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly Timer $timer,
        array $config = [],
        bool $enabled = true
    ) {
        $this->thresholds = $config['thresholds'] ?? [];
        $this->enabled = $enabled;
        $this->samplingRate = $config['sampling']['rate'] ?? 1.0;
        $this->degradedMode = $config['sampling']['degraded_mode'] ?? false;
        $this->degradedThresholdMs = $config['sampling']['degraded_threshold_ms'] ?? 1000;
    }

    public function addAnalyzer(AnalyzerInterface $analyzer): void
    {
        $this->analyzers[] = $analyzer;
    }

    public function addCollector(CollectorInterface $collector): void
    {
        $this->collectors[] = $collector;
    }

    /**
     * Starts tracking performance for a request.
     *
     * @param string $identifier Unique identifier for the tracking session
     */
    public function startTracking(string $identifier): void
    {
        if (!$this->shouldTrack()) {
            return;
        }

        $this->timer->start($identifier);
        $this->startTimes[$identifier] = ['memory' => memory_get_usage(true)];

        foreach ($this->collectors as $collector) {
            $collector->start($identifier);
        }
    }

    /**
     * Stops tracking and returns performance results.
     *
     * @param string $identifier Tracking session identifier
     * @param Request $request HTTP request
     * @param Response $response HTTP response
     * @return PerformanceResult Performance metrics and analysis
     */
    public function stopTracking(string $identifier, Request $request, Response $response): PerformanceResult
    {
        if (!$this->enabled || !isset($this->startTimes[$identifier])) {
            return new PerformanceResult();
        }

        $responseTime = (int) ($this->timer->stop($identifier) * 1000);
        $memoryUsage = memory_get_usage(true) - $this->startTimes[$identifier]['memory'];

        $result = new PerformanceResult();
        $result->setResponseTime($responseTime)
            ->setMemoryUsage($memoryUsage)
            ->setRoute($request->attributes->get('_route', 'unknown'))
            ->setMethod($request->getMethod())
            ->setStatusCode($response->getStatusCode());

        // Collect data in degraded mode only for essential collectors
        foreach ($this->collectors as $collector) {
            if ($this->isDegradedMode($responseTime) && !$collector instanceof DatabaseQueryCollector) {
                continue;
            }
            $collectorData = $collector->collect($identifier);
            $result->addCollectorData($collector::class, $collectorData);
        }

        // Run analyzers based on configuration
        $enabledAnalyzers = $this->getConfig()['analyzers'] ?? [];
        foreach ($this->analyzers as $analyzer) {
            $analyzerClass = (new \ReflectionClass($analyzer))->getShortName();
            $analyzerKey = strtolower(preg_replace('/Analyzer$/', '', $analyzerClass));
            if (!($enabledAnalyzers[$analyzerKey] ?? true)) {
                continue;
            }
            try {
                $analysisResult = $analyzer->analyze($request, $response, $result);
                $result->addAnalysisResult($analyzer::class, $analysisResult);
            } catch (\Exception $e) {
                $result->addMetadata('analyzer_error', [
                    'analyzer' => $analyzer::class,
                    'message' => $e->getMessage()
                ]);
            }
        }

        // Store results with error handling
        try {
            $this->storage->store($result);
        } catch (\Exception $e) {
            $result->addMetadata('storage_error', [
                'message' => $e->getMessage()
            ]);
        }

        unset($this->startTimes[$identifier]);

        return $result;
    }

    /**
     * Tracks performance of a CLI command.
     *
     * @param string $commandName Name of the command
     * @param callable $callback Command execution callback
     * @return mixed Command result
     */
    public function trackCommand(string $commandName, callable $callback): mixed
    {
        if (!$this->shouldTrack()) {
            return $callback();
        }

        $identifier = 'command_' . $commandName . '_' . uniqid();
        $this->timer->start($identifier);
        $this->startTimes[$identifier] = ['memory' => memory_get_usage(true)];

        try {
            $result = $callback();

            $responseTime = (int) ($this->timer->stop($identifier) * 1000);
            $memoryUsage = memory_get_usage(true) - $this->startTimes[$identifier]['memory'];

            $performanceResult = new PerformanceResult();
            $performanceResult->setResponseTime($responseTime)
                ->setMemoryUsage($memoryUsage)
                ->setRoute("command:{$commandName}")
                ->setMethod('CLI')
                ->setStatusCode(0);

            if ($responseTime > $this->thresholds['max_response_time_ms']) {
                $performanceResult->addMetadata('bottleneck', 'Response time exceeds threshold');
            }

            try {
                $this->storage->store($performanceResult);
            } catch (\Exception $e) {
                $performanceResult->addMetadata('storage_error', [
                    'message' => $e->getMessage()
                ]);
            }

            return $result;
        } finally {
            $this->timer->reset($identifier);
            unset($this->startTimes[$identifier]);
        }
    }

    /**
     * Checks if tracking should occur based on sampling rate.
     *
     * @return bool Whether to track the request
     */
    private function shouldTrack(): bool
    {
        if (!$this->enabled) {
            return false;
        }
        return $this->samplingRate >= 1.0 || random_int(0, 100) / 100 <= $this->samplingRate;
    }

    /**
     * Checks if degraded mode should be active.
     *
     * @param int $responseTime Response time in ms
     * @return bool Whether degraded mode is active
     */
    private function isDegradedMode(int $responseTime): bool
    {
        return $this->degradedMode && $responseTime > $this->degradedThresholdMs;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getThresholds(): array
    {
        return $this->thresholds;
    }

    /**
     * Gets the bundle configuration.
     *
     * @return array Configuration array
     */
    public function getConfig(): array
    {
        return [
            'thresholds' => $this->thresholds,
            'sampling' => [
                'rate' => $this->samplingRate,
                'degraded_mode' => $this->degradedMode,
                'degraded_threshold_ms' => $this->degradedThresholdMs,
            ],
            'analyzers' => $this->getConfig()['analyzers'] ?? [],
        ];
    }
}
