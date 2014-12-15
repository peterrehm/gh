<?php

namespace peterrehm\gh\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

/**
 * Set .gh.yml parameters
 */
class ConfigureCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('configure')
            ->setDescription('Define the settings');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Set the gh configuration');
        $questionHelper = $this->getHelper('question');
        $question = new Question('Please enter your GitHub token: ');
        $token = $questionHelper->ask($input, $output, $question);
        $question = new ConfirmationQuestion('Update configuration? [y/n] ', false);

        if (!$questionHelper->ask($input, $output, $question)) {
            return;
        }

        // create gh directory if it does not exist
        if (!is_dir($_SERVER['HOME'] . '/.gh/')) {
            mkdir($_SERVER['HOME'] . '/.gh/');
        }

        $configuration = [];
        $configuration['parameters']['token'] = $token;

        if (false !== file_put_contents($_SERVER['HOME'] . '/.gh/.gh.yml', Yaml::dump($configuration))) {
            $output->writeln('The configuration has been saved successfully.');
            return;
        }

        throw new \RuntimeException('Configuration could not be saved.');
    }
} 
