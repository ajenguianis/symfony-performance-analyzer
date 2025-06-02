<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Utils;

final class ComplexityCalculator
{
    private const COMPLEXITY_KEYWORDS = [
        'if' => 1,
        'else' => 1,
        'elseif' => 1,
        'switch' => 1,
        'case' => 1,
        'for' => 1,
        'foreach' => 1,
        'while' => 1,
        'do' => 1,
        'try' => 1,
        'catch' => 1,
        'finally' => 1,
        '&&' => 1,
        '||' => 1,
        '?' => 1, // ternary operator
    ];

    public function calculateControllerComplexity(string $controller): int
    {
        [$class, $method] = $this->parseController($controller);

        if (!class_exists($class)) {
            return 0;
        }

        $reflection = new \ReflectionClass($class);

        if (!$reflection->hasMethod($method)) {
            return 0;
        }

        $methodReflection = $reflection->getMethod($method);
        $startLine = $methodReflection->getStartLine();
        $endLine = $methodReflection->getEndLine();

        $filename = $reflection->getFileName();
        if (!$filename || !file_exists($filename)) {
            return 0;
        }

        $lines = file($filename);
        $methodCode = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        return $this->calculateCodeComplexity($methodCode);
    }

    public function calculateCodeComplexity(string $code): int
    {
        $complexity = 1; // Base complexity

        // Remove strings and comments to avoid false positives
        $code = $this->sanitizeCode($code);

        foreach (self::COMPLEXITY_KEYWORDS as $keyword => $weight) {
            $count = substr_count(strtolower($code), $keyword);
            $complexity += $count * $weight;
        }

        // Additional complexity for nested structures
        $nestingLevel = $this->calculateNestingComplexity($code);
        $complexity += $nestingLevel;

        return $complexity;
    }

    private function parseController(string $controller): array
    {
        if (str_contains($controller, '::')) {
            return explode('::', $controller, 2);
        }

        // Handle invokable controllers
        return [$controller, '__invoke'];
    }

    private function sanitizeCode(string $code): string
    {
        // Remove single line comments
        $code = preg_replace('/\/\/.*$/m', '', $code);

        // Remove multi-line comments
        $code = preg_replace('/\/\*.*?\*\//s', '', $code);

        // Remove strings
        $code = preg_replace('/"([^"\\\\]|\\\\.)*"/', '""', $code);
        $code = preg_replace("/'([^'\\\\]|\\\\.)*'/", "''", $code);

        return $code;
    }

    private function calculateNestingComplexity(string $code): int
    {
        $nestingLevel = 0;
        $maxNesting = 0;
        $currentNesting = 0;

        $tokens = token_get_all('<?php ' . $code);

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $tokenType = $token[0];
                $tokenValue = $token[1];

                if (in_array($tokenType, [T_IF, T_ELSEIF, T_FOR, T_FOREACH, T_WHILE, T_SWITCH, T_TRY])) {
                    $currentNesting++;
                    $maxNesting = max($maxNesting, $currentNesting);
                }
            } elseif ($token === '{') {
                // Opening brace might increase nesting
            } elseif ($token === '}') {
                $currentNesting = max(0, $currentNesting - 1);
            }
        }

        return $maxNesting > 1 ? $maxNesting - 1 : 0;
    }
}
