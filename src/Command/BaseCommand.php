<?php

namespace peterrehm\gh\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base gh command.
 */
abstract class BaseCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        if (false === file_exists($_SERVER['HOME'] . '/.gh/.gh.yml') && $this->getName() !== 'configure') {
            throw new \RuntimeException(sprintf('Could not find "%s". Please run the configure command.', $_SERVER['HOME'] . '/.gh/.gh.yml'));
        }
    }
} 
