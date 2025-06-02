<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('AlertComponent')]
final class AlertComponent
{
    public string $type = 'danger';
    public string $message;
}
