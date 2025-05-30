<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Tests\Analyzer;

use AA\PerformanceAnalyzer\Analyzer\CodeAnalyzer;
use PHPUnit\Framework\TestCase;

class CodeAnalyzerTest extends TestCase
{
    public function testDetectUnnecessaryForeach(): void
    {
        $code = <<<'PHP'
        <?php
        $results = [];
        foreach ($items as $item) {
            $results[] = $item['price'];
        }
        PHP;

        $file = sys_get_temp_dir() . '/test.php';
        file_put_contents($file, $code);

        $analyzer = new CodeAnalyzer();
        $result = $analyzer->analyzeFile($file);

        $this->assertNotEmpty($result['issues']);
        $this->assertStringContainsString('Unnecessary foreach', $result['issues'][0]['message']);
        $this->assertGreaterThan(0, $result['cognitive_complexity']);

        unlink($file);
    }
}
