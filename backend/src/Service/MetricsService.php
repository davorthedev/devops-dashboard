<?php

namespace App\Service;

use App\Entity\Metric;
use App\Repository\MetricRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class MetricsService
{
    private string $scriptPath;

    private EntityManagerInterface $entityManager;

    private MetricRepository $metricRepository;

    private LoggerInterface $logger;

    public function __construct(
        string $scriptPath,
        EntityManagerInterface $entityManager,
        MetricRepository $metricRepository,
        LoggerInterface $logger
    ) {
        if ('' === $scriptPath) {
            throw new \InvalidArgumentException('ScriptPath cannot be empty.');
        }
        if (!is_file($scriptPath)) {
            throw new \InvalidArgumentException(sprintf('Metrics script not found: %s', $scriptPath));
        }
        if (!is_executable($scriptPath)) {
            throw new \InvalidArgumentException(sprintf('Metrics script is not executable: %s', $scriptPath));
        }
        $this->scriptPath = $scriptPath;
        $this->entityManager = $entityManager;
        $this->metricRepository = $metricRepository;
        $this->logger = $logger;
    }

    /**
     * Run C script which gets usage of CPU, disk and memory and return data as JSON decoded array.
     */
    public function getMetrics(): array
    {
        $process = new Process([$this->scriptPath]);
        $process->setTimeout(10);
        $process->setIdleTimeout(5);
        try {
            $process->mustRun();
        } catch (\Exception $e) {
            $this->logger->error('Metrics script failed', [
                'error' => $e->getMessage(),
                'stderr' => $process->getErrorOutput(),
            ]);
            throw new \RuntimeException('Metrics script failed.', 1389, $e);
        }
        $output = trim($process->getOutput());
        $this->logger->debug('Metrics script output', ['output' => $output]);
        try {
            $data = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->error('Invalid JSON from metrics script', ['output' => $output]);
            throw new \RuntimeException('Metrics script returned invalid JSON.', 0, $e);
        }
        foreach (['cpu', 'memory', 'disk'] as $key) {
            if (!array_key_exists($key, $data)) {
                if (!\array_key_exists($key, $data) || !\is_numeric($data[$key])) {
                    throw new \RuntimeException(sprintf('Metrics JSON missing/invalid "%s".', $key));
                }
                $data[$key] = (float) $data[$key];
            }
        }

        // Stroring metric in this step is just for the development
        $metric = new Metric($data['cpu'], $data['memory'], $data['disk']);
        $this->entityManager->persist($metric);
        $this->entityManager->flush();

        return $data;
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