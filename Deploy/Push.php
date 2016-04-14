<?php

namespace App\Submodules\ToolsLaravelMicroservice\Deploy;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use SSH;
use Storage;
use App\Submodules\ToolsLaravelMicroservice\Deploy\GitManager;

use App\Submodules\ToolsLaravelMicroservice\Deploy\Traits\OutputTrait;
use App\Submodules\ToolsLaravelMicroservice\Deploy\Traits\VersionTrait;
use App\Submodules\ToolsLaravelMicroservice\Deploy\Traits\ServerPushTrait;

class Push extends Command
{
    use OutputTrait, VersionTrait, ServerPushTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'push
        {remote=none : The git remote to push to}
        {version=none : patch, minor, or major.  Defaults to no version change}
        {--branch= : Specify a git branch to push to (default is current)}
        {--a|amend : add the --amend flag for Git commit}
        {--b|build : Send to Jenkins server for CI build}
        {--d|docs : Regenerate documentation if using apidocs NPM}
        {--f|force : Force push the git repository}
        {--l|leave-untracked : Leave untracked files behind (do not auto-add)}
        {--s|submodule : Commit and push for a project\'s submodule(s)}
        {--t|tag : Update tag version}
        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pushes current branch to the specified server or repository.';

    protected $git;

    protected $pushTime;

    protected $envVars = [
        'DEPLOY_KEY',
        'REMOTE_GITDIR',
        'REMOTE_WORKTREE'
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(GitManager $git)
    {
        parent::__construct();

        $this->git = $git;

        $this->pushTime = time();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->preCheck();

        $this->checkSubmodule();

        $this->out('Starting push for current app.', 'comment', "\n ");

        $this->outputCurrentVersion();

        if ($this->argument('version') !== 'none') {
            $this->incrementVersion();
        }

        $this->outputSeparator();

        $this->setPushRemote();

        $this->outputCurrentBranch();

        $this->commitRepo();

        $this->pushRepo();
    }

    protected function preCheck()
    {
        // Check that _HOST entries exists for our remote servers
        $this->git->command = 'git remote';
        foreach ($this->git->exec() as $remote) {
            if ($remote == 'origin' || $remote == 'jenkins') {
                continue;
            }
            array_push($this->envVars, strtoupper($remote).'_HOST');
        }

        foreach($this->envVars as $var) {
            if (is_null(env($var, null))) {
                $this->outError('Missing env var: '.$var);
                throw new \Exception('Aborting.');
            }
        }

        $this->checkForCustomMaintenanceMode();

        if ($this->option('build')
            && !env('JENKINS_KEY', false)
        )
            throw new \Exception('Cannot push to Jenkins, no JENKINS_KEY '.
                'defined in .env file.');

        if ($this->argument('remote') != 'origin'
            && !env('DEPLOY_KEY', false)
        )
            throw new \Exception('Cannot push to '.$this->git->remote.', no '.
                'DEPLOY_KEY defined in .env file.');

        if ($this->argument('remote') == 'jenkins')
            throw new \Exception('Manual push to Jenkins server disabled.'.
                PHP_EOL.'Push to origin with the -b option to run a build.');

        // Make sure the app_root local disk exists in config/filesystems.php
        if (!array_key_exists('app_root', config('filesystems.disks'))) {
            throw new \Exception('You must create an `app_root` local disk in '.
                'config/filesystems.php, with root => base_path()');
        }
    }

    protected function checkForCustomMaintenanceMode()
    {
        $kernel = app()['Illuminate\Contracts\Http\Kernel'];
        $reflection = new \ReflectionClass($kernel);
        // Use PHP's Reflection class to access protected properties:
        $middleware = $reflection->getProperty('middleware');
        $middleware->setAccessible(true);
        $middleware = $middleware->getValue($kernel);

        $found = false;

        foreach($middleware as $ware) {
            $check = strpos($ware, 'App\Submodules\ToolsLaravelMicroservice'.
                '\App\Middleware\CheckForMaintenanceMode');
            if ($check !== false) {
                    $found = true;
            }
        }

        if (!$found) {
            $this->outError('Custom CheckForMaintenanceMode '.
                '(from ToolsLaravelMicroserviceHelper) not found.'.PHP_EOL.
                ' Unit tests will not run on server during deployment.');

            throw new \Exception('Aborting.');
        }
    }

    protected function checkSubmodule()
    {
        a:
        $subModules = ['Exit'];

        if (!$this->option('submodule')) {
            return;
        }

        if (!file_exists(base_path().'/.gitmodules')) {
            $this->outError('No submodules found for this app.');
            throw new \Exception('Aborting.');
        }

        // if(!Storage::disk('app_root')->exists('.gitmodules'));
        // currently doesn't work, seems to be bug in laravel.

        $gitModuleFile = Storage::disk('app_root')->get('.gitmodules');
        $gitModuleFile = explode("\n", $gitModuleFile);

        foreach($gitModuleFile as $line) {
            if (!str_contains($line, "[submodule ")) {
                continue;
            }

            $line = str_replace('[submodule "', '', $line);
            $line = str_replace('"]', '', $line);
            array_push($subModules, $line);
        }

        $which = $this->choice('Which submodule?', $subModules, 0);

        if ($which == 'Exit') {
            $this->abort();
        }

        $this->git->setDirectory($which.'/.git');
        $this->git->setWorkTree($which);

        $this->git->addAll();

        $this->git->checkout('master');

        $this->git->setStatus($this->git->getStatus());

        $this->out('Your last commit:', 'line', "\n ");
        $this->out('');
        $this->out($this->git->getLastCommit(), 'info', "\t");
        $this->out('');
        $this->out('New Commit:');
        $this->out('');
        $this->out($this->git->status, 'info', "\t");

        if (count($this->git->status) < 1) {
            $this->out('Working branch is clean, skipping.', 'line', "\n ");
        } else {
            $commitMessage = $this->ask('Commit message?');

            $this->git->commit($commitMessage);

            $this->git->setRemote('origin');
            $this->git->setBranch('master');
            $this->git->push();

            $this->out('Submodule updated.', 'comment', "\n ");
        }

        $this->outputSeparator();

        $postPushChoices = [
            'Exit',
            'Continue on to main push',
            'Update another submodule'
        ];

        $which = $this->choice('What now?', $postPushChoices);

        switch($which) {
            case 'Exit':
                exit;

            case 'Continue on to main push':
                $this->outputSeparator();
                $this->git = new GitManager;
                break;

            case 'Update another submodule':
                goto a;
                break;
        }
    }
}
