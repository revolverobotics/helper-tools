<?php

namespace App\Submodules\ToolsLaravelMicroservice\Deploy\Traits;

use SSH;

use App\Submodules\ToolsLaravelMicroservice\Deploy\Traits\ServerDeployTrait;
use App\Submodules\ToolsLaravelMicroservice\Deploy\Traits\ServerRollbackTrait;

trait ServerPushTrait
{
    use ServerDeployTrait, ServerRollbackTrait;

    protected function setPushRemote()
    {
        // Discover which remotes we have in the repo
        if ($this->argument('remote') == 'none') {
            $this->out('Remotes in this repo:', 'comment', ' ');

            $remotes = [];

            foreach ($this->git->getRemotes() as $remote) {
                $firstSpace = strpos($remote, "\t");
                $remoteName = substr($remote, 0, $firstSpace);

                if ($remoteName == 'upstream') {
                    continue;
                }

                if (!in_array($remoteName, $remotes)) {
                    array_push($remotes, $remoteName);
                    $this->out($remoteName, 'line', "\t");
                }
            }

            $this->git->setRemote($this->anticipate(
                'Push to which remote?',
                array_merge(['<abort>'], $remotes),
                '<abort>'
            ));
        } else {
            $this->git->setRemote($this->argument('remote'));
        }

        if ($this->git->remote == '<abort>') {
            exit;
        }
    }

    protected function outputCurrentBranch()
    {
        $this->out(
            'On branch [<cyan>'.$this->git->getCurrentBranch().'</cyan>]',
            'comment'
        );
    }

    protected function commitRepo()
    {
        if (is_null($this->git->status)) {
            $this->git->setStatus($this->git->getStatus());
        }

        if (count($this->git->status) < 1) {
            $this->out('Working branch is clean.', 'line', "\n ");
        } else {
            $this->out('Your last commit:', 'line', "\n ");
            $this->out('');
            $this->out($this->git->getLastCommit(), 'info', "\t");
            $this->out('');
            $this->out('New Commit:');
            $this->out('');
            $this->out($this->git->status, 'line', "\t");

            $untrackedFiles = false;
            $search_text = '??';
            array_filter($this->git->status, function ($el) use (
                $search_text,
                &$untrackedFiles
            ) {
                if (strpos($el, $search_text) !== false) {
                    $untrackedFiles = true;
                }
            });

            if ($untrackedFiles) {
                if (!$this->option('leave-untracked')) {
                    $this->git->addAll();
                } else {
                    $this->out(
                        'Leaving behind untracked files...',
                        'info',
                        ' '
                    );
                }
            }

            if ($this->option('amend')) {
                $this->git->amend = true;

                if ($this->confirm('Are you sure you want to amend the last '.
                    'commit? (potentially destructive) [y|N]')
                ) {
                    $this->git->commit('', '--amend');
                } else {
                    $this->outWarning('User aborted commit on --amend');
                    throw new \Exception('Aborting.');
                }
            } else {
                $commitMessage = $this->ask('Commit Message', '<abort>');

                if ($commitMessage == '<abort>') {
                    exit;
                }

                $this->git->commit($commitMessage);
            }
        }
    }

    protected function pushRepo()
    {
        $this->outputSeparator();

        if (is_null($this->git->branch) && is_null($this->option('branch'))) {
            $this->git->setBranch($this->git->getCurrentBranch());
        } else {
            $this->git->setBranch($this->option('branch'));
        }

        if ($this->git->remote == 'production') {
            $warning = 'ARE YOU SURE YOU WANT TO PUSH TO PRODUCTION??';

            if ($this->confirm($warning)) {
                $this->out(
                    'Pushing branch [<cyan>'.$this->git->branch.'</cyan>] to '.
                    'production server, hold on to your butts...',
                    'info'
                );
            } else {
                $this->outError('Push aborted.');
                throw new \Exception('Aborting.');
            }
        } else {
            $this->out(
                'Pushing branch [<cyan>'.$this->git->branch.
                '</cyan>] to remote [<cyan>'.$this->git->remote.
                '</cyan>]',
                'comment'
            );
        }

        if ($this->isRemoteServer()) {
            $this->checkEnvFiles();
            $this->getRollbackCommit();
            $this->putIntoMaintenanceMode();
        }

        $this->out('');

        $this->git->command = 'git push '.$this->git->remote.' '.
            $this->git->branch;

        if ($this->option('force') || $this->git->amend) {
            $this->git->addFlag('-f');
        }

        if ($this->isRemoteServer()) {
            $this->git->addDeployKey('aws');
        }

        if ($this->isOrigin() && $this->argument('version') != 'none') {
            $this->pushTags();
        }

        $this->git->exec();

        if ($this->option('build') && $this->isOrigin()) {
            $this->pushJenkins();
        } elseif ($this->option('build') && $this->isRemoteServer()) {
            $this->outError(
                'Can only build when pushing to origin, skipping.'
            );
        }

        if ($this->isRemoteServer()) {
            $this->runDeployCommands();
            $this->takeOutOfMaintenanceMode();
        }

        $this->outputSeparator();

        $this->out('Push completed.', 'comment');
        $this->out('');
    }

    protected function pushTags()
    {
        if ($this->git->branch != 'master' || !$this->isOrigin()) {
            $this->outError(
                "Can only tag branch `master`. Skipping version increment."
            );
            $this->out('');
            return;
        }

        if ($this->newVersion != false
            && $this->newVersion != $this->currentVersion
        ) {
            $this->git->setTag($this->newVersion);

            $this->out(
                'Tagging with new version: '.$this->newVersion,
                'line'
            );
        } else {
            $this->out(
                'Updating current version tag: '.$this->currentVersion,
                'line'
            );
            $this->out('');
            $this->git->updateCurrentTag($this->currentVersion);
            $this->out('');
        }

        $this->git->addFlag('--tags');
    }

    protected function pushJenkins()
    {
        $this->out(
            'Pushing to Jenkins server for CI build...',
            'comment',
            "\n "
        );

        // $this->out('');

        $this->git->setRemote('jenkins');

        // $this->git->updateCurrentTag($this->currentVersion);

        $this->out('');

        $this->git->command = 'git push jenkins '.$this->git->branch;

        // Always force
        $this->git->addFlag('-f');

        $this->git->addDeployKey('jenkins');

        // $this->git->addFlag('--tags');

        $this->git->exec();

        $this->out('Repo pushed to Jenkins, check dashboard '.
            'for build status.', 'comment', "\n ");
    }

    protected function isOrigin()
    {
        if ($this->git->remote == 'origin') {
            return true;
        }

        return false;
    }

    protected function isBuildServer()
    {
        if ($this->git->remote == 'jenkins') {
            return true;
        }

        return false;
    }

    protected function isRemoteServer()
    {
        if ($this->isOrigin() || $this->isBuildServer()) {
            return false;
        }

        return true;
    }
}
