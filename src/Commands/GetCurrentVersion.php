<?php

namespace ThomasFielding\Version\Commands;

use Illuminate\Console\Command;
use ThomasFielding\Version\Services\VersionService;

class GetCurrentVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version:current';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the current application version number';

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
        // Fetch the current version
        $version = $this->versionService->getVersionNumber();

        // Print the version number to the terminal
        $this->info('Current version: ' . $version);

        return $version;
    }
}
