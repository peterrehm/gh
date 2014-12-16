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
     * @return bool false if an error occurred otherwise true - errors in the recovery commands will not be watched
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
            return true;
        }

        // errors in the recovery mode will be ignored
        foreach($recoveryCommands as $command) {
            $this->runProcess($command);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'process';
    }
}
