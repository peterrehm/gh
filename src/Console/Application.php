<?php

namespace peterrehm\gh\Console;

use Github\Client;
use peterrehm\gh\Command\ConfigureCommand;
use peterrehm\gh\Command\MergeCommand;
use peterrehm\gh\Command\SHA2PRCommand;
use peterrehm\gh\Helper\GitHelper;
use peterrehm\gh\Helper\ProcessHelper;
use peterrehm\gh\Helper\TemplatingHelper;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Yaml\Yaml;

class Application extends BaseApplication
{
    const NAME = 'gh';
    const VERSION = '1.0-alpha1';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $username = null;

    /**
     * @var string
     */
    private $repository = null;

    public function __construct()
    {
        parent::__construct(static::NAME, static::VERSION);

        $this->getHelperSet()->set(new GitHelper());
        $this->getHelperSet()->set(new ProcessHelper());
        $this->getHelperSet()->set(new TemplatingHelper());

        $this->getRepositoryData();
        $this->authenticateClient();

        $this->addCommands([
            new ConfigureCommand(),
            new MergeCommand($this->username, $this->repository),
            new SHA2PRCommand($this->username, $this->repository),
        ]);

    }

    /**
     * Authenticates github client
     */
    private function authenticateClient()
    {
        $fileName = $_SERVER['HOME'] . '/.gh/.gh.yml';

        if(file_exists($fileName) && $content = file_get_contents($fileName)) {
            $config = Yaml::parse($content);
            $token = isset($config['parameters']['token']) ? $config['parameters']['token'] : null;

            if (null === $token) {
                return;
            }

            $this->client = new Client();
            $this->client->authenticate($token, null, Client::AUTH_HTTP_TOKEN);
            return;
        }
    }

    /**
     * Fetch the repository information
     */
    private function getRepositoryData()
    {
        /** @var ProcessHelper $processHelper */
        $processHelper = $this->getHelperSet()->get('process');
        $gitRoot = $processHelper->runProcess('git rev-parse --show-toplevel');

        // if gitRoot is null either git is not installed or the application
        // has been executed in a directory not managed by git
        if (null === $gitRoot) {
            throw new \RuntimeException('Git root could not be detected. Run this command in your projects folder.');
        }

        // composer.json is missing
        if (!file_exists($gitRoot . '/composer.json')) {
            return;
        }

        $content = file_get_contents($gitRoot . '/composer.json');
        $config = json_decode($content, true);

        // name attribute is missing
        if (!isset($config['name'])) {
            return;
        }

        $parsed = explode('/', $config['name']);

        // invalid name string with either no or multiple separators
        if (count($parsed) !== 2) {
            return;
        }

        // invalid name string
        if (strlen($parsed[0]) === 0 || strlen($parsed[1]) === 0) {
            return;
        }

        $this->username = $parsed[0];
        $this->repository = $parsed[1];
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getRepository()
    {
        return $this->repository;
    }
}
