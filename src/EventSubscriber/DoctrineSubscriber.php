<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\EventSubscriber;

use AA\PerformanceAnalyzer\Service\Collector\DatabaseQueryCollector;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events as ORMEvents;

final class DoctrineSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly DatabaseQueryCollector $queryCollector
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::postConnect,
            ORMEvents::postFlush,
        ];
    }

    public function postConnect(ConnectionEventArgs $args): void
    {
        $connection = $args->getConnection();
        $connection->getConfiguration()->setSQLLogger($this->queryCollector);
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        // Additional processing after flush if needed
    }
}
