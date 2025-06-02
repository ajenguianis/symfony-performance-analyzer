<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\EventSubscriber;

use AA\PerformanceAnalyzer\Event\PerformanceDataEvent;
use AA\PerformanceAnalyzer\Service\PerformanceTracker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class PerformanceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly PerformanceTracker $performanceTracker,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly bool $enabled = true
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1024],
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $identifier = $this->generateIdentifier($request);

        $this->performanceTracker->startTracking($identifier);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $identifier = $this->generateIdentifier($request);

        $result = $this->performanceTracker->stopTracking($identifier, $request, $response);

        // Dispatch performance data event for additional processing
        $performanceEvent = new PerformanceDataEvent($result, $request, $response);
        $this->eventDispatcher->dispatch($performanceEvent);
    }

    private function generateIdentifier(\Symfony\Component\HttpFoundation\Request $request): string
    {
        return sprintf(
            'request_%s_%s_%s',
            $request->getMethod(),
            $request->getPathInfo(),
            uniqid()
        );
    }
}
