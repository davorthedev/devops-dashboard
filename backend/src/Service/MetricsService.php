<?php

namespace App\Service;

use App\Entity\Metric;
use App\Repository\MetricRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Process\Process;

class MetricsService
{
    private string $scriptPath;

    private EntityManagerInterface $entityManager;

    private MetricRepository $metricRepository;

    public function __construct(
        string $scriptPath,
        EntityManagerInterface $entityManager,
        MetricRepository $metricRepository
    ) {
        if ('' === $scriptPath) {
            throw new \InvalidArgumentException('ScriptPath cannot be empty.');
        }
        $this->scriptPath = $scriptPath;
        $this->entityManager = $entityManager;
        $this->metricRepository = $metricRepository;
    }

    /**
     * Run C script which gets usage of CPU, disk and memory and return data as JSON decoded array.
     */
    public function getMetrics(): array
    {
        $process = new Process([$this->scriptPath]);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('Metrics script failed: %s', $process->getErrorOutput()));
        }
        $data = json_decode($process->getOutput(), true);

        if (isset($data['cpu'], $data['disk'], $data['memory'])
            && is_numeric($data['cpu']) && is_numeric($data['disk']) && is_numeric($data['memory'])) {
            $metric = new Metric(
                (float) $data['cpu'],
                (float) $data['memory'],
                (float) $data['disk']
            );
            $this->entityManager->persist($metric);
            $this->entityManager->flush();
        }

        return $data ?? [];
    }

    public function getMetricsFromDate(?string $fromParam): array
    {
        if ($fromParam) {
            $fromDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $fromParam);
            $errors = \DateTimeImmutable::getLastErrors();
            if (false === $fromDate || $errors['error_count'] > 0 || $errors['warning_count'] > 0) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid datetime format: %s. Expected Y-m-d H:i:s', $fromParam)
                );
            }
        } else {
            $fromDate = (new \DateTimeImmutable('now'))->sub(new \DateInterval('P1D'));
        }
        $metrics = $this->metricRepository->findFromDate($fromDate);

        return array_map(fn(Metric $metric) => [
            'recordedAt' => $metric->getCreatedAt()->format('Y-m-d H:i:s'),
            'cpu' => $metric->getCpu(),
            'disk' => $metric->getDisk(),
            'memory' => $metric->getMemory(),
        ], $metrics);
    }
}