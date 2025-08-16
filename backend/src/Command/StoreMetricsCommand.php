<?php

namespace App\Command;

use App\Entity\Metric;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class StoreMetricsCommand extends Command
{
    protected  static $defaultName = 'app:store-metrics';

    private EntityManagerInterface $em;

    private string $scriptPath;

    public function __construct(EntityManagerInterface $em, string $scriptPath)
    {
        parent::__construct();
        if ('' === $scriptPath) {
            throw new \InvalidArgumentException('Script path cannot be empty');
        }
        $this->em = $em;
        $this->scriptPath = $scriptPath;
    }

    protected function configure()
    {
        $this
            ->setDescription('Stores system metrics (cpu, memory, disk usage) in the database')
            ->setHelp('Runs the sys_stats script and stores the returned metrics.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $process = new Process(['bash', $this->scriptPath]);

        $process->run();
        if (!$process->isSuccessful()) {
            $output->writeln('<error>Failed to run sys_stats script</error>');
            $output->writeln($process->getErrorOutput());

            return Command::FAILURE;
        }
        $data = json_decode($process->getOutput(), true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $output->writeln(sprintf('<error>Invalid JSON: %s</error>', json_last_error_msg()));

            return Command::FAILURE;
        }
        if (!isset($data['cpu'], $data['memory'], $data['disk'])) {
            $output->writeln('<error>Data are not set</error>');

            return Command::FAILURE;
        }
        if (!is_numeric($data['cpu']) || !is_numeric($data['memory']) || !is_numeric($data['disk'])) {
            $output->writeln('<error>Data must be numeric</error>');

            return Command::FAILURE;
        }
        $metric = new Metric(
            (float) $data['cpu'],
            (float) $data['memory'],
            (float) $data['disk']
        );
        $this->em->persist($metric);
        $this->em->flush();
        $output->writeln('<info>Metric stored</info>');

        return Command::SUCCESS;
    }
}
