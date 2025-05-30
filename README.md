# Symfony Performance Analyzer Bundle

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.0-blue.svg)](https://php.net/)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E6.0%7C%5E7.0-blue.svg)](https://symfony.com/)

A comprehensive toolkit for monitoring and optimizing Symfony applications, featuring real-time performance metrics, N+1 query detection, automated reporting, a visual dashboard, and AI-powered optimization suggestions.

## Features

- 🚀 **Real-time Performance Metrics**: Monitor request time, database queries, memory usage, and cache efficiency.
- 🔍 **N+1 Query Detection**: Identify and resolve N+1 query issues in database operations.
- 📊 **Automated Reporting**: Generate reports in JSON, CSV, or HTML formats.
- 📈 **Visual Dashboard**: Visualize performance trends and metrics at `/performance-dashboard`.
- 🤖 **AI-Powered Recommendations**: Receive actionable optimization suggestions using configurable AI models (e.g., OpenAI).
- 🔔 **Performance Alerts**: Configure thresholds for query time and memory usage to trigger warnings.

## Installation

Install the bundle via Composer:

```bash
composer require 2a/symfony-performance-analyzer
```

Enable the bundle in `config/bundles.php`:

```php
return [
    // ...
    AA\PerformanceAnalyzer\PerformanceAnalyzerBundle::class => ['all' => true],
];
```

Copy public resources (e.g., Chart.js) to your project:

```bash
php bin/console assets:install --symlink
```

## Configuration

Create a configuration file at `config/packages/aa_performance_analyzer.yaml`:

```yaml
aa_performance_analyzer:
    enabled: true
    base_template: '@PerformanceAnalyzer/dashboard.html.twig'
    collectors:
        database: true
        memory: true
        http: true
        cache: true
    ai_integration:
        enabled: false  # Enable AI recommendations (requires .env variables)
    thresholds:
        slow_query_time: 100  # Max query time in ms before alert
        memory_limit_warning: 128  # Max memory in MB before alert
```

For AI-powered recommendations, add the following to your `.env` file:

```env
AI_API_KEY=your-api-key-here
AI_ENDPOINT=https://api.openai.com/v1/chat/completions
AI_MODEL=gpt-4-turbo
```

**Note**: AI recommendations require `ai_integration.enabled: true` in `aa_performance_analyzer.yaml` and non-empty `AI_API_KEY`, `AI_ENDPOINT`, and `AI_MODEL` in `.env`.

## Usage

### CLI Analysis

Run performance analysis via the command line:

```bash
# Basic analysis (JSON output)
php bin/console aa:analyze:performance

# With AI recommendations (requires AI configuration)
php bin/console aa:analyze:performance --output=json

# Generate HTML report
php bin/console aa:analyze:performance --output=html > report.html

# Analyze specific code path
php bin/console aa:analyze:performance --code-path=src/App
```

### Web Dashboard

Access the interactive dashboard at `/performance-dashboard` to view performance metrics, charts, and recent analyses. Ensure `base_template` is configured in `aa_performance_analyzer.yaml`.

### Metrics Collected

| Metric | Description |
|--------|-------------|
| **Request Time** | Total execution time per request |
| **Database Queries** | Count, duration, and N+1 issues in SQL queries |
| **Memory Usage** | Peak memory consumption in MB |
| **Cache Efficiency** | Cache hit/miss ratios |
| **N+1 Issues** | Detected N+1 query patterns |
| **Code Quality** | Cognitive complexity and code issues (e.g., unnecessary loops) |

### AI-Powered Recommendations

To enable AI recommendations:
1. Set `ai_integration.enabled: true` in `aa_performance_analyzer.yaml`.
2. Configure `AI_API_KEY`, `AI_ENDPOINT`, and `AI_MODEL` in `.env`.
3. Run the analysis command:

   ```bash
   php bin/console aa:analyze:performance --output=json
   ```

Example recommendation:
```json
{
  "issue": "N+1 query issue detected",
  "suggestion": "Use JOIN or eager loading for query: SELECT * FROM users",
  "priority": "high",
  "context": "Occurrences: 10"
}
```

If AI is disabled or fails, fallback static recommendations are provided.

## Advanced Features

### Custom Analyzers

Extend the bundle by creating custom analyzers that implement `AnalyzerInterface`:

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

Register the analyzer in `config/services.yaml`:

```yaml
App\Performance\Analyzer\CustomAnalyzer:
    tags: ['aa_performance_analyzer.analyzer']
```

Custom analyzer results will appear in the `performance` section of the analysis output, keyed by the analyzer’s class name (e.g., `CustomAnalyzer`).

### Dashboard Customization

Override the default dashboard template by creating a file at `templates/bundles/PerformanceAnalyzerBundle/dashboard.html.twig`. The default template uses a locally bundled Chart.js (no CDN dependency).

Example override:
```twig
{% extends '@PerformanceAnalyzer/dashboard.html.twig' %}

{% block body %}
    <div class="container">
        <h1>Custom Performance Dashboard</h1>
        {{ parent() }}
    </div>
{% endblock %}
```

Alternatively, specify a custom base template in `aa_performance_analyzer.yaml`:

```yaml
aa_performance_analyzer:
    base_template: 'custom_base.html.twig'
```

## Uninstallation

To remove the bundle and clean up resources (e.g., database tables, logs, assets):

```bash
composer remove aa/performance-analyzer
```

This triggers the `aa:purge-package` command, which deletes:
- The `aa_performance_analysis` database table.
- Public assets (`public/bundles/performanceanalyzer/chart.js`).
- AI recommendation logs (`var/log/ai_recommendations.log`).

## Testing

Run the test suite:

```bash
composer test
```

## Contributing

1. Fork the repository.
2. Create a feature branch (`git checkout -b feature/my-feature`).
3. Commit your changes (`git commit -am 'Add my feature'`).
4. Push to the branch (`git push origin feature/my-feature`).
5. Open a Pull Request.

## License

MIT License. See the [LICENSE](LICENSE) file for details.
