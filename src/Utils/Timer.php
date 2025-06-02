<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Utils;

final class Timer
{
    private array $timers = [];

    public function start(string $name): void
    {
        $this->timers[$name] = microtime(true);
    }

    public function stop(string $name): float
    {
        if (!isset($this->timers[$name])) {
            return 0.0;
        }

        $elapsed = microtime(true) - $this->timers[$name];
        unset($this->timers[$name]);

        return $elapsed;
    }

    public function getElapsed(string $name): float
    {
        if (!isset($this->timers[$name])) {
            return 0.0;
        }

        return microtime(true) - $this->timers[$name];
    }

    public function reset(string $name): void
    {
        unset($this->timers[$name]);
    }

    public function resetAll(): void
    {
        $this->timers = [];
    }
}
