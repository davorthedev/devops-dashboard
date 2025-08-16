<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\MetricRepository::class)]
class Metric
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'float')]
    private float $cpu;

    #[ORM\Column(type: 'float')]
    private float $memory;

    #[ORM\Column(type: 'float')]
    private float $disk;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(float $cpu, float $memory, float $disk)
    {
        if ($cpu < 0 || $cpu > 100) {
            throw new \InvalidArgumentException("CPU usage must be between 0 and 100");
        }
        if ($memory < 0) {
            throw new \InvalidArgumentException("Memory usage cannot be negative");
        }
        if ($disk < 0) {
            throw new \InvalidArgumentException("Disk usage cannot be negative");
        }
        $this->cpu = $cpu;
        $this->memory = $memory;
        $this->disk = $disk;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCpu(): float
    {
        return $this->cpu;
    }

    public function getMemory(): float
    {
        return $this->memory;
    }

    public function getDisk(): float
    {
        return $this->disk;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}