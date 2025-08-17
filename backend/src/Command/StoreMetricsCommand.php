<?php

namespace App\Command;

use App\Entity\Metric;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:store-metrics',
    description: 'Stores system metrics (cpu, memory, disk) in the database'
)]
class StoreMetricsCommand extends Command
{
    private EntityManagerInterface $em;

    private LoggerInterface $logger;

    private string $scriptPath;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, string $scriptPath)
    {
        parent::__construct();
        if ('' === $scriptPath) {
            throw new \InvalidArgumentException('Script path cannot be empty');
        }
        $this->em = $em;
        $this->logger = $logger;
        $this->scriptPath = $scriptPath;
    }

    protected function configure()
    {
        $this
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Process timeout in seconds', 15)
            ->addOption('idle-timeout', null, InputOption::VALUE_REQUIRED, 'Idle timeout in seconds', 5);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (!is_file($this->scriptPath) || !is_readable($this->scriptPath)) {
            $io->error(sprintf('Script path "%s" does not exist or is not readable', $this->scriptPath));

            return self::FAILURE;
        }
        $process = new Process([$this->scriptPath]);
        $process->setTimeout($input->getOption('timeout'));
        $process->setIdleTimeout($input->getOption('idle-timeout'));
        try {
            $process->mustRun();
        } catch (\Exception $e) {
            $io->error('Failed to run sys_stats script');
            $stdErr = $process->getErrorOutput();
            if ('' !== $stdErr) {
                $io->writeln($stdErr);
            }
            $this->logger->error('sys_stats failed', ['exception' => $e, 'stderr' => $stdErr]);

            return self::FAILURE;
        }
        try {
            $data = json_decode($process->getOutput(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $io->error(sprintf('Invalid JSON from sys_stats: %s', $e->getMessage()));
            $this->logger?->warning('Invalid JSON from sys_stats', [
                'exception' => $e,
                'raw_output' => $process->getOutput(),
            ]);

            return self::FAILURE;
        }
        foreach (['cpu', 'memory', 'disk'] as $key) {
            if (!array_key_exists($key, $data)) {
                $io->error(sprintf('Missing key "%s" in sys_stats output.', $key));

                return self::FAILURE;
            }
            if (!is_numeric($data[$key])) {
                $io->error(sprintf('Value for "%s" must be numeric, got %s.', $key, gettype($data[$key])));

                return self::FAILURE;
            }
            $data[$key] = (float) $data[$key];
        }
        try {
            $metric = new Metric($data['cpu'], $data['memory'], $data['disk']);
        } catch(\Exception $e) {
            $io->error($e->getMessage());
            $this->logger->error('sys_stats failed', ['exception' => $e]);

            return self::FAILURE;
        }
        try {
            $this->em->persist($metric);
            $this->em->flush();
        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to persist metric: %s', $e->getMessage()));
            $this->logger->error('Persist metric failed', ['exception' => $e]);

            return self::FAILURE;
        }
        $io->success(sprintf(
            'Metric stored: cpu=%.2f, memory=%.2f, disk=%.2f',
            $data['cpu'],
            $data['memory'],
            $data['disk']
        ));

        return self::SUCCESS;
    }
}
