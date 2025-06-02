<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Event;

use AA\PerformanceAnalyzer\Model\PerformanceResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

final class PerformanceDataEvent extends Event
{
    public function __construct(
        private readonly PerformanceResult $performanceResult,
        private readonly Request $request,
        private readonly Response $response
    ) {}

    public function getPerformanceResult(): PerformanceResult
    {
        return $this->performanceResult;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
