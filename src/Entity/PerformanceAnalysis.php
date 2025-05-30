<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PerformanceAnalysisRepository::class)]
#[ORM\Table(name: 'aa_performance_analysis')]
class PerformanceAnalysis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $controllerAction;

    #[ORM\Column(type: 'float')]
    private float $responseTime;

    #[ORM\Column(type: 'integer')]
    private int $queryCount;

    #[ORM\Column(type: 'bigint')]
    private int $memoryUsage;

    #[ORM\Column(type: 'json')]
    private array $nPlusOneIssues = [];

    #[ORM\Column(type: 'json')]
    private array $cacheStats = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getControllerAction(): string
    {
        return $this->controllerAction;
    }
    public function setControllerAction(string $controllerAction): self
    {
        $this->controllerAction = $controllerAction;
        return $this;
    }
    public function getResponseTime(): float
    {
        return $this->responseTime;
    }
    public function setResponseTime(float $responseTime): self
    {
        $this->responseTime = $responseTime;
        return $this;
    }
    public function getQueryCount(): int
    {
        return $this->queryCount;
    }
    public function setQueryCount(int $queryCount): self
    {
        $this->queryCount = $queryCount;
        return $this;
    }
    public function getMemoryUsage(): int
    {
        return $this->memoryUsage;
    }
    public function setMemoryUsage(int $memoryUsage): self
    {
        $this->memoryUsage = $memoryUsage;
        return $this;
    }
    public function getNPlusOneIssues(): array
    {
        return $this->nPlusOneIssues;
    }
    public function setNPlusOneIssues(array $nPlusOneIssues): self
    {
        $this->nPlusOneIssues = $nPlusOneIssues;
        return $this;
    }
    public function getCacheStats(): array
    {
        return $this->cacheStats;
    }
    public function setCacheStats(array $cacheStats): self
    {
        $this->cacheStats = $cacheStats;
        return $this;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
