# Symfony Performance Analyzer Bundle

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue.svg)](https://php.net/)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E7.0-blue.svg)](https://symfony.com/)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)](https://travis-ci.org/2a-aa/symfony-performance-analyzer)
[![Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](https://codecov.io/gh/2a-aa/symfony-performance-analyzer)

A powerful toolkit for monitoring and optimizing Symfony applications, offering real-time performance metrics, N+1 query detection, cognitive complexity analysis, automated reporting, a secure web dashboard, and seamless CI/CD integration.

## Features

- 🚀 **Real-time Performance Metrics**: Monitor request response time, database queries, memory usage, and CLI command performance.
- 🔍 **N+1 Query Detection**: Identify and resolve N+1 query issues with detailed analysis.
- 🧠 **Cognitive Complexity Analysis**: Detect complex code structures (threshold > 10) to improve maintainability.
- 📊 **Automated Reporting**: Generate HTML, JSON, or SVG badge reports for CI/CD pipelines.
- 📈 **Secure Web Dashboard**: Visualize performance trends and metrics at `/_performance`.
- 🔔 **Performance Alerts**: Configurable thresholds for response time, memory usage, query count, and complexity.
- 💾 **Flexible Storage**: Store data using Doctrine ORM (`DatabaseStorage`) or JSON files (`FileStorage`) with retention policies.
- 🌐 **Internationalization**: Supports English and French translations.
- 🛠 **Extensibility**: Add custom analyzers, collectors, and event listeners.
- ✅ **Standards Compliance**: Enforced with PHPStan (max level), PHPUnit (100% coverage), ECS, and Rector.
- ⚡ **Performance Optimizations**: Request sampling, degraded mode, and circuit breaker for external services.

## Installation

Install the bundle via Composer:

```bash
composer require aa/symfony-performance-analyzer
```

Enable the bundle in `config/bundles.php`:

```php
return [
    // ...
    AA\PerformanceAnalyzer\PerformanceAnalyzerBundle::class => ['all' => true],
];
```

Generate and apply database migrations (for database storage):

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

Install assets for the web dashboard:

```bash
php bin/console assets:install --symlink
```

## Configuration

Create a configuration file at `config/packages/symfony_performance_analyzer.yaml`:

```yaml
symfony_performance_analyzer:
    enabled: true
    storage:
        type: database  # or 'file'
        file_path: '%kernel.project_dir%/var/performance'
        retention:
            days: 30  # Retain data for 30 days
            max_records: 10000  # Maximum number of records
    analyzers:
        memory: true
        n_plus_one: true
        cognitive_complexity: true
    thresholds:
        max_memory_mb: 256  # Maximum memory in MB
        max_response_time_ms: 500  # Maximum response time in ms
        max_db_queries: 50  # Maximum number of database queries
        max_cognitive_complexity: 10  # Maximum cognitive complexity score
    sampling:
        rate: 1.0  # Analyze 100% of requests (0.0 to 1.0)
        degraded_mode: false  # Enable degraded mode under high load
        degraded_threshold_ms: 1000  # Trigger degraded mode above this response time (ms)
    circuit_breaker:
        enabled: false  # Enable circuit breaker for external services
        failure_threshold: 5  # Number of failures before opening circuit
        retry_timeout: 60  # Seconds to wait before retrying
    dashboard:
        enabled: true
        route_prefix: /_performance
        security:
            enable_firewall: false
            allowed_ips: []  # Restrict dashboard access to specific IPs
    profiler:
        enabled: true
        toolbar: true  # Enable Symfony debug toolbar integration
    ci_cd:
        enabled: false
        level: warning  # Report level: error, warning, notice
        output_format: json  # Report format: json, html
```

For file-based storage, ensure the storage directory is writable:

```bash
mkdir -p var/performance
chmod -R 775 var/performance
```

## Usage

### CLI Commands

Analyze performance and generate reports:

```bash
# Generate an HTML performance report
php bin/console performance:generate-report --format=html --output=report.html

# Generate a JSON report for CI/CD
php bin/console performance:generate-report --format=json --dry-run > ci-report.json

# Generate a CI/CD badge
php bin/console performance:generate-ci-badge --output=badge.svg

# Profile a CLI command
php bin/console performance:profile app:my-command
```

Example JSON report output:

```json
{
    "logs": [
        {
            "route": "app_home",
            "response_time": 100,
            "memory_usage": 32,
            "query_count": 5
        }
    ],
    "stats": {
        "avg_response_time": 100,
        "avg_memory_usage": 32,
        "max_query_count": 10
    },
    "issues": [
        {
            "type": "high_complexity",
            "message": "Complexity 12 > 10",
            "severity": "high"
        }
    ]
}
```

### Web Dashboard

Access the performance dashboard at `/_performance`. Secure it by enabling the firewall and restricting IP access:

```yaml
symfony_performance_analyzer:
    dashboard:
        security:
            enable_firewall: true
            allowed_ips: ['127.0.0.1', '192.168.1.0/24']
```

Customize the dashboard route in `config/routes.yaml`:

```yaml
performance_dashboard:
    path: /_performance
    controller: AA\PerformanceAnalyzer\Controller\DashboardController::index
```

### Debug Toolbar

The bundle integrates with Symfony’s debug toolbar, displaying request time, query count, memory usage, and performance issues. Enable it in the configuration:

```yaml
symfony_performance_analyzer:
    profiler:
        toolbar: true
```

### Performance Metrics

The bundle collects and analyzes the following metrics:

| Metric | Description | Expected Range | Source |
|--------|-------------|----------------|--------|
| **Request Time** | HTTP request execution time | 0–500 ms | `PerformanceTracker`, `Timer` |
| **Database Queries** | Query count, duration, and N+1 issues | 0–50 queries | `DatabaseQueryCollector`, `QueryAnalyzer` |
| **Memory Usage** | Peak memory consumption | 0–256 MB | `MemoryAnalyzer` |
| **Cognitive Complexity** | Code complexity score | 0–10 | `CognitiveComplexityAnalyzer` |
| **CLI Performance** | Command execution time | 0–5000 ms | `PerformanceTracker` |
| **Issues** | Performance violations detected | 0–10 issues | `AnalysisResult` |

### Custom Analyzers

Create a custom analyzer by implementing `AnalyzerInterface`:

```php
namespace App\Analyzer;

use AA\PerformanceAnalyzer\Model\AnalysisResult;
use AA\PerformanceAnalyzer\Model\PerformanceResult;
use AA\PerformanceAnalyzer\Service\Analyzer\AnalyzerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomAnalyzer implements AnalyzerInterface
{
    public function analyze(Request $request, Response $response, PerformanceResult $result): AnalysisResult
    {
        $analysisResult = new AnalysisResult();
        if ($response->getStatusCode() >= 500) {
            $analysisResult->addIssue('server_error', [
                'message' => 'Server error detected',
                'status_code' => $response->getStatusCode(),
                'severity' => 'high'
            ]);
        }
        return $analysisResult;
    }
}
```

Register the analyzer in `config/services.yaml`:

```yaml
App\Analyzer\CustomAnalyzer:
    tags: ['performance.analyzer']
```

### Custom Collectors

Create a custom collector by implementing `CollectorInterface`:

```php
namespace App\Collector;

use AA\PerformanceAnalyzer\Service\Collector\CollectorInterface;

class CustomCollector implements CollectorInterface
{
    private array $data = [];

    public function start(string $identifier): void
    {
        $this->data[$identifier] = ['start_time' => microtime(true)];
    }

    public function collect(string $identifier): array
    {
        return [
            'custom_duration' => microtime(true) - $this->data[$identifier]['start_time']
        ];
    }
}
```

Register the collector:

```yaml
App\Collector\CustomCollector:
    tags: ['performance.collector']
```

## Performance Considerations

- **Request Sampling**: Set `sampling.rate` to a value less than 1.0 (e.g., 0.1 for 10% of requests) in production to reduce overhead.
- **Degraded Mode**: Enable `sampling.degraded_mode` to skip non-essential collectors when response time exceeds `degraded_threshold_ms`.
- **Circuit Breaker**: Activate `circuit_breaker.enabled` to protect against external service failures, with configurable failure thresholds and retry timeouts.
- **Data Retention**: Configure `storage.retention` to limit database or file storage growth, specifying retention days and maximum records.
- **Overhead**: Performance tests ensure tracking overhead is below 5ms per request.

## Testing

Run the test suite:

```bash
vendor/bin/phpunit
```

Perform static analysis and linting:

```bash
vendor/bin/phpstan analyze
vendor/bin/ecs check
vendor/bin/rector process --dry-run
```

The bundle includes:
- **Unit Tests**: Cover all services and components (100% coverage).
- **Integration Tests**: Validate end-to-end workflows with database storage.
- **Performance Tests**: Measure tracking overhead.
- **Edge Case Tests**: Handle database failures and high-load scenarios.

## Uninstallation

Remove the bundle:

```bash
composer remove aa/symfony-performance-analyzer
```

Drop database tables (if using database storage):

```bash
php bin/console doctrine:query:sql "DROP TABLE performance_log, performance_stat"
```

Remove configuration files and assets:

```bash
rm config/packages/symfony_performance_analyzer.yaml
rm -rf var/performance
```

## Contributing

1. Fork the repository.
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Commit changes: `git commit -am 'Add my feature'`
4. Push to the branch: `git push origin feature/my-feature`
5. Open a Pull Request.

Ensure all tests pass and adhere to coding standards (ECS, Rector, PHPStan).

## License

This bundle is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.