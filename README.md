# Symfony Performance Analyzer Bundle

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-8.3%2B-blue.svg)](https://php.net/)
[![Symfony Version](https://img.shields.io/badge/symfony-7.0%2B-blue.svg)](https://symfony.com/)

A comprehensive performance monitoring and optimization toolkit for Symfony applications.

## Features

- 🚀 Real-time performance metrics
- 🔍 N+1 Query detection
- 📊 Automated reporting (JSON, CSV, HTML)
- 📈 Visual dashboard
- 🤖 AI-powered recommendations (GitHub Copilot integration)
- 🔔 Performance alerts

## Installation

```bash
composer require ajenguianis/symfony-performance-analyzer
```

Enable the bundle in `config/bundles.php`:

```php
return [
    // ...
    AA\PerformanceAnalyzer\PerformanceAnalyzerBundle::class => ['all' => true],
];
```

## Configuration

Add basic configuration to `config/packages/performance_analyzer.yaml`:

```yaml
performance_analyzer:
    enable_profiler: true        # Enable Symfony profiler integration
    enable_dashboard: true       # Enable web dashboard
    sample_rate: 100            # Percentage of requests to analyze (0-100)
    thresholds:
        query_time: 100         # Max query time in ms before alert
        memory_usage: 128       # Max memory in MB before alert
```

## Usage

### CLI Analysis

```bash
# Basic analysis
php bin/console aa:analyze:performance

# With Copilot recommendations
php bin/console aa:analyze:performance --copilot

# Generate HTML report
php bin/console aa:analyze:performance --output=html > report.html
```

### Web Dashboard

Access the dashboard at `/performance-dashboard` or via the Symfony profiler.

## Metrics Collected

| Metric | Description |
|--------|-------------|
| Request Time | Total execution time per request |
| Database Queries | Count and duration of SQL queries |
| Memory Usage | Peak memory consumption |
| Cache Efficiency | Cache hit/miss ratios |
| N+1 Issues | Detected N+1 query patterns |

## Advanced Features

### GitHub Copilot Integration

1. Set your Copilot token in `.env`:

```env
COPILOT_API_TOKEN=your_token_here
```

2. Enable in configuration:

```yaml
performance_analyzer:
    copilot:
        enabled: true
        model: 'gpt-4'  # Model to use
```

### Custom Analyzers

Create custom analyzers by implementing `AnalyzerInterface`:

```php
namespace App\Performance\Analyzer;

use AA\PerformanceAnalyzer\Analyzer\AnalyzerInterface;

class CustomAnalyzer implements AnalyzerInterface
{
    public function analyze(): array
    {
        return [
            'custom_metric' => 42,
        ];
    }
}
```

Register your analyzer in `services.yaml`:

```yaml
App\Performance\Analyzer\CustomAnalyzer:
    tags: ['performance_analyzer.analyzer']
```

## Testing

Run the test suite:

```bash
composer test
```

## Contributing

1. Fork the project
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

## License

MIT License. See the LICENSE file for details.