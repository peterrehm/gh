<?php

namespace peterrehm\gh\Command;

use peterrehm\gh\Helper\GitHelper;
use peterrehm\gh\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Show merge commit information
 */
class SHA2PRCommand extends GitHubBaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('sha2pr')
            ->setDescription('Find the pull request for a given sha')
            ->addArgument('sha', InputArgument::REQUIRED, 'sha of commit')
            ->addOption(
                'branch',
                'b',
                InputOption::VALUE_REQUIRED,
                'master'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $prNumber = $this->prInformation($input, $output);
        $questionHelper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Open PR in your default browser? [y/n] ', false);

        if (!$questionHelper->ask($input, $output, $question)) {
            return;
        }

        /** @var ProcessHelper $processHelper */
        $processHelper = $this->getHelper('process');
        $processHelper->runProcess(sprintf('open https://github.com/%s/%s/pull/%d', $input->getOption('username'), $input->getOption('repository'), $prNumber));
    }

    /**
     * Print the PR information according to GitHub or gh merge commit scheme
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return integer Pull request number
     */
    private function prInformation(InputInterface $input, OutputInterface $output)
    {
        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        $matches = [];
        $prCommit = $gitHelper->getPrForSha($input->getArgument('sha'), $input->getOption('branch'));

        if (null === $prCommit || $prCommit === '') {
            throw new \RuntimeException(sprintf('No merge commit could be found for SHA "%s" in Branch "%s"', $input->getArgument('sha'), $input->getOption('branch')));
        }

        // merge commit scheme when using gh
        if (1 === preg_match('/(.{7})\s(.*)\s#(\d*)\s(.*)\s\((.*)\)/', $prCommit, $matches)) {
            $output->writeln(sprintf('Found SHA in <info>%s</info> PR #<info>%d</info> with title "<info>%s</info>"', $matches[2], $matches[3], $matches[4]));
            return $matches[3];
        }

        // merge commit information when using github
        if (1 === preg_match('/(.{7})\sMerge pull request\s#(\d*)\s/', $prCommit, $matches)) {
            $output->writeln(sprintf('Found SHA in PR #<info>%d</info>', $matches[2]));
            return $matches[2];
        }

        throw new \RuntimeException(sprintf('Merge commit information "%s" could not be parsed', $prCommit));
    }
} 
