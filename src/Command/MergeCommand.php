<?php

namespace peterrehm\gh\Command;

use peterrehm\gh\Helper\GitHelper;
use peterrehm\gh\Helper\TemplatingHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MergeCommand extends GitHubBaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('merge')
            ->setDescription('Merges the pull request given')
            ->addArgument('pr', InputArgument::REQUIRED, 'Pull Request number')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Enforce the merge if PR is in unstable state')
            ->addOption('branch', null, InputOption::VALUE_REQUIRED, 'Target branch', 'master');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        $branch = $input->getOption('branch');

        // fetch the PR information
        $client = $this->getClient();
        $pr = $client->pullRequest()->show($input->getOption('username'), $input->getOption('repository'), $input->getArgument('pr'));
        $commits = $client->pullRequest()->commits($input->getOption('username'), $input->getOption('repository'), $input->getArgument('pr'));

        $gitHelper->ensureRemoteConfiguration($input->getOption('username'), $pr['base']['repo']['clone_url']);

        // Skip if PR is already merged
        if (true === $pr['merged']) {
            $output->writeln('<error>Pull request has been merged already.</error>');
            return;
        }

        // If there are conflicts they have to be resolved manually
        if (false === $pr['mergeable']) {
            $output->writeln('<error>Pull request is not mergeable. Please rebase the PR and fix the conflicts.</error>');
            return;
        }

        $question = new ChoiceQuestion(
            'Please select the PR type:',
            array('feature', 'bug', 'minor')
        );

        $prType = $questionHelper->ask($input, $output, $question);
        $question = new ConfirmationQuestion(sprintf('Merge <info>%s</info> PR <info>#%s</info> "<info>%s</info>" by <info>%s</info> into <info>(%s)</info>? [y/n] ', $prType, $pr['number'], $pr['title'], $pr['user']['login'], $branch), false);

        if (!$questionHelper->ask($input, $output, $question)) {
            return;
        }

        // by default merges should only be possible if the mergeable state is clean which
        // means that there are no conflicts by CI
        if ('unstable' === $pr['mergeable_state'] && $input->hasOption('force') === false) {
            $output->writeln('<error>PR is not in clean state. Fix the CI errors or use the --force option.</error>');
            return;
        }

        /** @var TemplatingHelper $templatingHelper */
        $templatingHelper = $this->getHelper('templating');
        $commitMessage = $templatingHelper->render(
            'commit_message.tpl.twig',
            array('prType' => $prType, 'pr' => $pr, 'commits' => $commits)
        );

        $merged = $gitHelper->mergeRemote($input->getOption('username'), $branch, $input->getArgument('pr'), $commitMessage);

        // check if the PR has been merged
        if (false === $merged) {
            $output->writeln('<error>The pull request could not be merged.</error>');
            return;
        }

        $output->writeln('The pull request has been merged successfully.');
    }
} 
