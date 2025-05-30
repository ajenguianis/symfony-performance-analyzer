<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Collector;

use Symfony\Component\HttpFoundation\RequestStack;

class HttpCollector
{
    public function __construct(private RequestStack $requestStack) {}

    public function collect(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request ? [
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'controller' => $request->attributes->get('_controller'),
        ] : [];
    }
}
