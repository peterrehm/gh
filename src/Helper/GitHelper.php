<?php

namespace peterrehm\gh\Helper;

use Symfony\Component\Console\Helper\Helper;

class GitHelper extends Helper
{
    /**
     * @return null|string Branch name
     */
    public function getCurrentBranch()
    {
        /** @var ProcessHelper $processHelper */
        $processHelper = $this->getHelperSet()->get('process');
        return $processHelper->runProcess('git rev-parse --abbrev-ref HEAD');
    }

    public function workingDirectoryIsClean()
    {
        /** @var ProcessHelper $processHelper */
        $processHelper = $this->getHelperSet()->get('process');
        return '' === $processHelper->runProcess('git status --porcelain --untracked-files=no');
    }

    /**
     * Checks if remote is configured as git remote and adds otherwise
     *
     * @param string $remote
     * @param string $remoteUrl
     */
    public function ensureRemoteConfiguration($remote, $remoteUrl)
    {
        /** @var ProcessHelper $processHelper */
        $processHelper = $this->getHelperSet()->get('process');
        $result = $processHelper->runProcess(sprintf('git remote show %s', $remote));

        if (null === $result) {
            $processHelper->runProcess(sprintf('git remote add %s %s', $remote, $remoteUrl));
        }
    }

    /**
     * Executes a remote merge
     *
     * @param string $username
     * @param string $targetBranch
     * @param integer $pullRequest
     * @param string $message
     * @return bool true if all commands have been executed successfully
     */
    public function mergeRemote($username, $targetBranch, $pullRequest, $message)
    {
        /** @var ProcessHelper $processHelper */
        $processHelper = $this->getHelperSet()->get('process');

        $previousBranch = $this->getCurrentBranch();
        $workingDirectoryClean = $this->workingDirectoryIsClean();

        $commands = [];

        # stash all current changes
        $commands[] = 'git stash';

        # fetch the reference to the pull request
        $commands[] = sprintf('git fetch %s pull/%d/head:pr_%d', $username, $pullRequest, $pullRequest);

        # checkout the current state of the remote repository
        $commands[] = sprintf('git checkout %s/%s -b tmp_%s', $username, $targetBranch, $targetBranch);

        # perform the merge
        $commands[] = sprintf('git merge pr_%d --no-ff -m "%s"', $pullRequest, $message);

        # delete the reference after merge
        $commands[] = sprintf('git branch -d pr_%d',$pullRequest);

        # push to the remote
        $commands[] = sprintf('git push %s HEAD:%s', $username, $targetBranch);

        # checkout the previous branch
        $commands[] = sprintf('git checkout %s', $previousBranch);

        # delete the tmp branch after merge
        $commands[] = sprintf('git branch -d tmp_%s', $targetBranch);

        if (false ===$workingDirectoryClean) {
            # restore the stashes
            $commands[] = sprintf('git stash pop', $previousBranch);
        }

        $recoveryCommands = [
            sprintf('git branch -d pr_%d',$pullRequest),
            sprintf('git checkout %s', $previousBranch),
            sprintf('git branch -d tmp_%s', $targetBranch),
        ];

        // recover stashes only if working directory was dirty
        if (false ===$workingDirectoryClean) {
            # restore the stashes
            $recoveryCommands[] = sprintf('git stash pop', $previousBranch);
        }

        return $processHelper->runProcesses($commands, $recoveryCommands);
    }

    /**
     * Fetches the merge commit message for a given sha
     *
     * @param string $sha
     * @param string $branch
     * @return null|string
     */
    public function getPrForSha($sha, $branch)
    {
        /** @var ProcessHelper $processHelper */
        $processHelper = $this->getHelperSet()->get('process');
        return $processHelper->runProcess(sprintf('git log --merges --ancestry-path --oneline %s..%s | tail -n 1', $sha, $branch));
    }

    /**
     * Fetch the latest tag
     *
     * @return null|string
     */
    public function getLastTag()
    {
        /** @var ProcessHelper $processHelper */
        $processHelper = $this->getHelperSet()->get('process');
        return $processHelper->runProcess('git describe --tags --abbrev=0');
    }

    /**
     * Fetches the merge commit descriptions for a given revision or since initial commit
     *
     * @param string $reference
     * @return null|string
     */
    public function showChangelog($reference = null)
    {
        /** @var ProcessHelper $processHelper */
        $processHelper = $this->getHelperSet()->get('process');

        $command = 'git log --merges --format="%s"';

        if (null !== $reference) {
            $command .= ' ' . $reference;
        }

        return $processHelper->runProcess($command);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'git';
    }
}
