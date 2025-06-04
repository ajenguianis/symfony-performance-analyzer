<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Entity;

use AA\PerformanceAnalyzer\Repository\PerformanceStatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PerformanceStatRepository::class)]
#[ORM\Table(name: 'performance_stat')]
class PerformanceStat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $route;

    #[ORM\Column(type: Types::INTEGER)]
    private int $totalRequests = 0;

    #[ORM\Column(type: Types::FLOAT)]
    private float $avgResponseTime = 0.0;

    #[ORM\Column(type: Types::FLOAT)]
    private float $avgMemoryUsage = 0.0;

    #[ORM\Column(type: Types::FLOAT)]
    private float $avgQueryCount = 0.0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $maxResponseTime = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $maxMemoryUsage = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $maxQueryCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and setters...
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getTotalRequests(): int
    {
        return $this->totalRequests;
    }

    public function setTotalRequests(int $totalRequests): self
    {
        $this->totalRequests = $totalRequests;
        return $this;
    }

    public function incrementRequests(): self
    {
        $this->totalRequests++;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAvgResponseTime(): float
    {
        return $this->avgResponseTime;
    }

    public function setAvgResponseTime(float $avgResponseTime): self
    {
        $this->avgResponseTime = $avgResponseTime;
        return $this;
    }

    public function getAvgMemoryUsage(): float
    {
        return $this->avgMemoryUsage;
    }

    public function setAvgMemoryUsage(float $avgMemoryUsage): self
    {
        $this->avgMemoryUsage = $avgMemoryUsage;
        return $this;
    }

    public function getAvgQueryCount(): float
    {
        return $this->avgQueryCount;
    }

    public function setAvgQueryCount(float $avgQueryCount): self
    {
        $this->avgQueryCount = $avgQueryCount;
        return $this;
    }

    public function getMaxResponseTime(): int
    {
        return $this->maxResponseTime;
    }

    public function setMaxResponseTime(int $maxResponseTime): self
    {
        $this->maxResponseTime = $maxResponseTime;
        return $this;
    }

    public function getMaxMemoryUsage(): int
    {
        return $this->maxMemoryUsage;
    }

    public function setMaxMemoryUsage(int $maxMemoryUsage): self
    {
        $this->maxMemoryUsage = $maxMemoryUsage;
        return $this;
    }

    public function getMaxQueryCount(): int
    {
        return $this->maxQueryCount;
    }

    public function setMaxQueryCount(int $maxQueryCount): self
    {
        $this->maxQueryCount = $maxQueryCount;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
