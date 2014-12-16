<?php

namespace peterrehm\gh\Command;

use peterrehm\gh\Helper\GitHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Show changelog information for next release
 */
class ChangelogCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('changelog')
            ->setDescription('Show the changelog since last tag or initial commit')
            ->addOption('range', null, InputOption::VALUE_REQUIRED, 'Reference range live v0.1..v0.2');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');
        $referenceRange = $this->getReferenceRange($input->getOption('range'));
        $changelog = $gitHelper->showChangelog($referenceRange);

        if (null === $changelog) {
            throw new \RuntimeException('Changelog could not be retrieved. Check the range option or your repository.');
        }

        if (null === $referenceRange) {
            $output->writeln('<comment>Changelog</comment> since initial commit:');
        } else {
            $output->writeln(sprintf('<comment>Changelog</comment> for reference range <info>%s</info>:', $referenceRange));
        }

        // skip if no changes have been detected
        if (empty($changelog)) {
            $output->writeln('No merge commits have been found.');
            return;
        }

        $changelog = explode(PHP_EOL, $changelog);
        foreach ($changelog as $entry) {
            $output->writeln('* ' . $entry);
        }
    }

    /**
     * Defines the reference range or returns null
     *
     * @param string|null $range
     * @return null|string
     */
    private function getReferenceRange($range = null)
    {
        /** @var GitHelper $gitHelper */
        $gitHelper = $this->getHelper('git');

        // if a range is specified always use the range
        if (null !== $range) {
            return $range;
        }

        // checks if a tag exists
        $lastTag = $gitHelper->getLastTag();

        // if there is a tag use the reference TAG..HEAD to select all since the last tag
        if (null !== $lastTag) {
            return $lastTag . '..HEAD';
        }

        // no reference needed
        return null;
    }
}
