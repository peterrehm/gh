<?php

namespace peterrehm\gh\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Process\Process;

class ProcessHelper extends Helper
{
    /**
     * Runs a command and returns the trimmed result or null in case of a failure.
     *
     * @param string $command
     *
     * @return null|string
     */
    public function runProcess($command)
    {
        $process = new Process($command);
        $process->run();

        if (false === $process->isSuccessful()) {
            return null;
        }

        return trim($process->getOutput());
    }

    /**
     * Runs the processes sequentially and triggers the execution of recovery processes
     * in case a process failed
     *
     * @param array $commands
     * @param array $recoveryCommands
     */
    public function runProcesses(array $commands, array $recoveryCommands = [])
    {
        $commandError = false;
        foreach ($commands as $command) {
            $result = $this->runProcess($command);
            if (null === $result) {
                $commandError = true;
                break;
            }
        }

        if (false === $commandError) {
            return;
        }

        foreach($recoveryCommands as $command) {
            $this->runProcess($command);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'process';
    }
}
