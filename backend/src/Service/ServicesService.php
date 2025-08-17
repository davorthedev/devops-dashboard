<?php

namespace App\Service;

use Symfony\Component\Process\Process;

class ServicesService
{
    private string $rubyScriptPath;

    public function __construct(string $rubyScriptPath)
    {
        if ('' === $rubyScriptPath) {
            throw new \InvalidArgumentException('Ruby script path cannot be empty.');
        }
        if (!is_file($rubyScriptPath) || !is_readable($rubyScriptPath)) {
            throw new \InvalidArgumentException(sprintf('Script not found or not readable: %s', $rubyScriptPath));
        }
        $this->rubyScriptPath = $rubyScriptPath;
    }

    public function manageService(string $action, string $serviceName): string
    {
        $process = new Process(['ruby', $this->rubyScriptPath, $action, $serviceName]);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('Service management failed: %s', $process->getErrorOutput()));
        }

        return trim($process->getOutput());
    }
}