<?php

namespace peterrehm\gh\Command;

use Github\Client;
use peterrehm\gh\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class for all GitHub functions.
 */
abstract class GitHubBaseCommand extends BaseCommand
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $repository;

    /**
     * @param string $username
     * @param string $repository
     */
    public function __construct($username, $repository)
    {
        $this->username = $username;
        $this->repository = $repository;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->addOption(
                'username',
                'u',
                InputOption::VALUE_REQUIRED,
                'GitHub username name',
                $this->username
            );

        $this
            ->addOption(
                'repository',
                'r',
                InputOption::VALUE_REQUIRED,
                'GitHub repository name',
                $this->repository
            );
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getOption('username');
        $repository = $input->getOption('repository');

        if (empty($username)) {
            throw new \RuntimeException('Invalid username has been provided.');
        }

        if (empty($repository)) {
            throw new \RuntimeException('Invalid repository has been provided.');
        }

        $output->writeln(sprintf('Working on <comment>%s/%s</comment>', $username, $repository));
    }

    /**
     * @return Client|null
     */
    public function getClient()
    {
        /** @var Application $application */
        $application = $this->getApplication();
        return $application->getClient();
    }
} 
