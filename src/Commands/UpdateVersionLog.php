<?php

namespace ThomasFielding\Version\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use ThomasFielding\Version\Services\VersionService;

class UpdateVersionLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version:log:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update an existing version history log for the application';

    /**
     * Constructor
     *
     * @param VersionService $versionService
     *
     * @return void
     */
    public function __construct(
        VersionService $versionService
    ) {
        parent::__construct();

        $this->versionService = $versionService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get the current branch id
        $git = trim(shell_exec('git rev-parse ' . shell_exec('git rev-parse --abbrev-ref HEAD')));

        // Error check: Ensure a branch has been created and pushed to remote
        if (!$git) {
            $this->error('You need to commit your current git branch before updating a log');
            return;
        }

        // Error check: Ensure a log exists for this branch to update
        if (!$logs = $this->versionService->getLogsByBranchId($git)) {
            $this->error('A log does not exist for this git branch');
            return;
        }

        $template = $logs[0];

        $template->timestamp = Carbon::now()->getTimestamp();

        // Store the template as a new file/log
        file_put_contents(
            $this->versionService->getRoot() . '/' . $this->versionService->getFileById($template->id),
            json_encode($template, JSON_PRETTY_PRINT)
        );

        // Print to the console
        $this->info('Updated the version log');
    }
}
