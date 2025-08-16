<?php

namespace App\Service;

use Symfony\Component\Process\Process;

class LogsService
{
    private string $pythonScriptPath;

    public function __construct(string $pythonScriptPath)
    {
        if ('' === $pythonScriptPath) {
            throw new \InvalidArgumentException('Python script path can not be empty.');
        }
        $this->pythonScriptPath = $pythonScriptPath;
    }

    public function getAnomalies(): array
    {
        $process = new Process(['python3', $this->pythonScriptPath]);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('Log parser failed: %s', $process->getErrorOutput()));
        }

        return json_decode($process->getOutput(), true) ?? [];
    }
}