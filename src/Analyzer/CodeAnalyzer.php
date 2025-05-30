<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Analyzer;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class CodeAnalyzer
{
    private array $issues = [];
    private int $complexity = 0;

    public function analyzeFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['error' => "File not found: $filePath"];
        }

        $code = file_get_contents($filePath);
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class($this) extends NodeVisitorAbstract {
            private CodeAnalyzer $analyzer;

            public function __construct(CodeAnalyzer $analyzer)
            {
                $this->analyzer = $analyzer;
            }

            public function enterNode(Node $node): ?int
            {
                // Detect control structures for cognitive complexity
                if (
                    $node instanceof Node\Stmt\Foreach_ ||
                    $node instanceof Node\Stmt\For_ ||
                    $node instanceof Node\Stmt\While_ ||
                    $node instanceof Node\Stmt\If_ ||
                    $node instanceof Node\Stmt\Switch_ ||
                    $node instanceof Node\Stmt\Catch_
                ) {
                    $this->analyzer->incrementComplexity();

                    // Check for unnecessary foreach
                    if ($node instanceof Node\Stmt\Foreach_) {
                        $this->checkUnnecessaryForeach($node);
                    }
                }

                // Detect nested structures
                if ($node instanceof Node\Stmt\If_ || $node instanceof Node\Stmt\Foreach_) {
                    $this->analyzer->incrementComplexity(count($node->getSubNodeNames()));
                }

                return null;
            }

            private function checkUnnecessaryForeach(Node\Stmt\Foreach_ $node): void
            {
                $stmts = $node->stmts;
                if (count($stmts) === 1 && $stmts[0] instanceof Node\Stmt\Expression) {
                    $expr = $stmts[0]->expr;
                    if ($expr instanceof Node\Expr\ArrayDimFetch && $expr->var instanceof Node\Expr\Variable) {
                        $this->analyzer->addIssue('Unnecessary foreach: Could use array_column or array_map', $node->getLine());
                    }
                }
            }
        });

        $traverser->traverse($ast);

        return [
            'issues' => $this->issues,
            'cognitive_complexity' => $this->complexity,
        ];
    }

    public function addIssue(string $message, int $line): void
    {
        $this->issues[] = ['message' => $message, 'line' => $line];
    }

    public function incrementComplexity(int $amount = 1): void
    {
        $this->complexity += $amount;
    }

    public function getIssues(): array
    {
        return $this->issues;
    }

    public function getComplexity(): int
    {
        return $this->complexity;
    }
}
