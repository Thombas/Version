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
    protected $signature = 'version:log {level=patch} {--description=} {--author=}';

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
     * @return int
     */
    public function handle()
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

        // Get the current timestamp to append to the file
        $timestamp = Carbon::now()->format('Y_m_d_His');

        // The filename to use
        $filename = $timestamp . '_' . $level . '_' . $this->versionService->getVersionNumber($level);

        // Copy the template
        $this->buildTemplate($git, $filename, $level, $this->option('description'), $this->option('author'));

        // Print to the console
        $this->info('Generated a new ' . $level . ' log: ' . $filename);
    }

    /**
     * Store the template
     *
     * @param string $git The git id of the current branch
     * @param string $filename The name under which to store the new file
     * @param string $level The level to store (Major, Minor, Patch)
     * @param string|null $description The description for the log
     * @param string|null $author The autor to credit
     *
     * @return void
     */
    protected function buildTemplate(
        string $git,
        string $filename,
        string $level,
        ?string $description,
        ?string $author
    ): void {
        // Fetch the template
        $template = (Object) array_merge(
            json_decode(file_get_contents(dirname(__FILE__) . '/../Stubs/template.json'), true),
            (array) config('version.template', [])
        );

        // Update the template values
        $template->author = $author;
        $template->branch_id = $git;
        $template->description = $description;
        $template->id = Str::uuid();
        $template->type = $level;
        $template->timestamp = Carbon::now()->getTimestamp();

        // Store the template as a new file/log
        file_put_contents($this->versionService->getRoot() . '/' . $filename . '.json', json_encode($template, JSON_PRETTY_PRINT));
    }
}
