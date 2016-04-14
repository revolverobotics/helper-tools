# Push command for git & server deployment
Automate your git commit, amend and push commands

#### Prerequisites
This command assumes that you are using a private key (typically in .PEM format) to SSH into your remote server.

Visit [this page](https://alvinabad.wordpress.com/2013/03/23/how-to-specify-an-ssh-key-file-with-the-git-command/) for information on setting up the PKEY option for remote git SSH usage.

Following the page, the accompanying script `git-ssh.sh` must be added to your PATH.

`git-ssh.sh`:
```Shell
#!/bin/sh
if [ -z "$PKEY" ]; then
# if PKEY is not specified, run ssh using default keyfile
ssh "$@"
else
ssh -i "$PKEY" ec2-user@"$@"
fi

#chmod +x this script
```

#### Installation
The namespace (and folder structure) should be as: `App\Submodules\ToolsLaravelPushCommand`

Within `app/Console/Kernel.php`:
```PHP
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Submodules\ToolsLaravelPushCommand\Push::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
	//$schedule->command('pingHomepage')->cron('*/2 * * * *');
	// for cron jobs
    }

}
```

Set the path to the `DEPLOY_KEY` and `REMOTE_WORKTREE` in your `.env` file, e.g.:
```Shell
# .env
DEPLOY_KEY=/home/user/.ssh/deploykey.pem
REMOTE_WORKTREE=/path/to/app/
```
