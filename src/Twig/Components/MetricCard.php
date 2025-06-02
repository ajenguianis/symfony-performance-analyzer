<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('MetricCard')]
final class MetricCard
{
    public string $title;
    public float $value;
    public string $unit;
    public string $label;
}
