<?php

declare(strict_types=1);

namespace AA\PerformanceAnalyzer\Entity;

use AA\PerformanceAnalyzer\Repository\PerformanceLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PerformanceLogRepository::class)]
#[ORM\Table(name: 'performance_log')]
#[ORM\Index(columns: ['created_at'], name: 'idx_performance_log_created_at')]
#[ORM\Index(columns: ['route'], name: 'idx_performance_log_route')]
class PerformanceLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $route;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $method;

    #[ORM\Column(type: Types::INTEGER)]
    private int $responseTime;

    #[ORM\Column(type: Types::INTEGER)]
    private int $memoryUsage;

    #[ORM\Column(type: Types::INTEGER)]
    private int $queryCount;

    #[ORM\Column(type: Types::FLOAT)]
    private float $queryTime;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $cognitiveComplexity = null;

    #[ORM\Column(type: Types::JSON)]
    private array $metadata = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $requestId;

    #[ORM\Column(type: Types::INTEGER)]
    private int $statusCode;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->requestId = md5(uniqid('', true));
    }

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

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function getResponseTime(): int
    {
        return $this->responseTime;
    }

    public function setResponseTime(int $responseTime): self
    {
        $this->responseTime = $responseTime;
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

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    public function setQueryCount(int $queryCount): self
    {
        $this->queryCount = $queryCount;
        return $this;
    }

    public function getQueryTime(): float
    {
        return $this->queryTime;
    }

    public function setQueryTime(float $queryTime): self
    {
        $this->queryTime = $queryTime;
        return $this;
    }

    public function getCognitiveComplexity(): ?int
    {
        return $this->cognitiveComplexity;
    }

    public function setCognitiveComplexity(?int $cognitiveComplexity): self
    {
        $this->cognitiveComplexity = $cognitiveComplexity;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }
}
