<?php

namespace ThomasFielding\Version\Commands;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use ThomasFielding\Version\Services\VersionService;
use Throwable;

class AddVersionLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version:log {level=patch} {--description=} {--author=} {--tags=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new version history log update to the application';

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
     * @return void
     */
    public function handle(): void
    {
        // Fetch the git branch id
        try {
            $git = $this->versionService->getGitBranchId();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return;
        }

        // Fetch the correct level (Major/Minor/Patch)
        $level = strtolower($this->argument('level'));

        // If not a valid level, default to patch
        $level = in_array($level, ['major', 'minor', 'patch']) ? $level : 'patch';

        // Copy the template
        $filename = $this->versionService->createLog(
            $git,
            $level,
            $this->option('description'),
            $this->option('author'),
            $this->option('tags')
        );

        // Print to the console
        $this->info('Generated a new ' . $level . ' log: ' . $filename);
    }
}
