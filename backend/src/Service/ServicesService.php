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
        $this->rubyScriptPath = $rubyScriptPath;
    }

    /**
     * @param string $action TODO dodati enumerator?
     * @param string $serviceName TODO dodati enumerator?
     */
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