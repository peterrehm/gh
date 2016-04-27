<?php

namespace peterrehm\gh\Command;

use peterrehm\gh\Helper\GitHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sync branch from main repository to local branch and back to remote
 */
class SyncCommand extends GitHubBaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('sync')
            ->setDescription('Syncs main repository branch to local branch and sync back to remote')
            ->addArgument('branch', InputArgument::OPTIONAL, 'branch name', 'master')
            ->addOption(
                'remote',
                '',
                InputOption::VALUE_REQUIRED,
                'origin',
                'origin'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        $synced = $gitHelper->syncBranches($input->getOption('username'), $input->getArgument('branch'), $input->getOption('remote'));

        if (false === $synced) {
            $output->writeln('<error>Could not sync the branches.</error>');
            return;
        }

        $output->writeln(sprintf('The branch <info>%s</info> is now in sync with the remotes (<info>%s</info>) and (<info>%s</info>)', $input->getArgument('branch'), $input->getOption('username'), $input->getOption('remote')));
    }
} 
