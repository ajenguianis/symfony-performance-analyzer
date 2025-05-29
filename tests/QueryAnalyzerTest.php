<?php

namespace Tests\AA\PerformanceAnalyzer;

use AA\PerformanceAnalyzer\Analyzer\QueryAnalyzer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class QueryAnalyzerTest extends TestCase
{
    public function testAnalyzeWithNoQueries(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $analyzer = new QueryAnalyzer($em);

        $result = $analyzer->analyze();

        $this->assertEquals(0, $result['total_queries']);
        $this->assertEquals(0, $result['total_time']);
    }

    // Ajoutez d'autres tests pour la classe QueryAnalyzer selon vos besoins
}
