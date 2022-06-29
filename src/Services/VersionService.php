<?php

namespace ThomasFielding\Version\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use ThomasFielding\Version\Exceptions\DuplicateLogException;
use ThomasFielding\Version\Exceptions\UncommittedBranchException;

class VersionService
{
    /** @var */
    protected string $root;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(
        ?string $root = null,
        ?string $initial = null,
        ?array $template = null
    ) {
        $this->root = base_path() . '/' . ($root ?? config('version.root', './version'));
        $this->initial = $initial ?? config('version.initial', '0.0.0');
        $this->template = $template ?? config('version.template', []);
    }

    /**
     * Fetch the version number as a complete unit or specified partial
     *
     * @param ?string $version The version to increment if fetching future (Major, Minor or Patch)
     *
     * @return string
     */
    protected function buildVersionNumber(
        ?string $version = null
    ): string {
        $logs = $this->getLogs();

        $major = $this->getInitialVersionNumber(0);
        $minor = $this->getInitialVersionNumber(1);
        $patch = $this->getInitialVersionNumber(2);

        foreach ($logs as $log) {
            switch ($log->type) {
                case 'major':
                    $major++;
                    $minor = 0;
                    $patch = 0;
                    break;
                case 'minor':
                    $minor++;
                    $patch = 0;
                    break;
                default:
                    $patch++;
                    break;
            }
        }

        return $version
            ? ${$version}
            : implode('.', [
                $major,
                $minor,
                $patch
            ]);
    }

    /**
     * Check that the provided directory exists (and if not then create it)
     *
     * @param string $directory The directory to scan for
     *
     * @return void
     */
    protected function checkDirExists($directory): void
    {
        // Break the directory into steps/segments
        $steps = explode('/', $directory);

        // Loop through and build each step of the directory
        foreach ($steps as $key => $step) {
            $glue = implode('/', array_slice($steps, 0, $key + 1));

            if ($glue && !is_dir($glue)) {
                mkdir($glue);
            }
        }
    }

    /**
     * Create a new log entry
     *
     * @param string $git The git id of the current branch
     * @param string $level The level to store (Major, Minor, Patch)
     * @param string|null $description The description for the log
     * @param string|null $author The autor to credit
     * @param string|null $tags A tag to associate with the version release (Feature, Hotfix, Bugfix, Epic, etc.)
     *
     * @return string
     */
    public function createLog(
        string $git,
        string $level,
        ?string $description,
        ?string $author,
        ?string $tags
    ): string {
        // Create the current timestamp
        $timestamp = Carbon::now()->format('Y_m_d_His');

        // Generate the file name
        $filename = $timestamp . '_' . $level . '_' . $this->getVersionNumber($level);

        // Fetch the template
        $template = (Object) array_merge(
            json_decode(file_get_contents(dirname(__FILE__) . '/../Stubs/template.json'), true),
            (array) $this->template
        );

        // Update the template values
        $template->author = $author;
        $template->branch_id = $git;
        $template->description = $description;
        $template->id = Str::uuid();
        $template->type = $level;
        $template->timestamp = Carbon::now()->getTimestamp();
        $template->tags = $tags;

        // Store the template as a new file/log
        file_put_contents(
            $this->getRoot() . '/' . $filename . '.json',
            json_encode($template, JSON_PRETTY_PRINT)
        );

        // Return the full filename
        return $filename . '.json';
    }

    /**
     * Extract the json data from a provided log file
     *
     * @param string $filename The full filename to extract from
     *
     * @return object
     */
    protected function extractLogData(
        string $filename
    ): object {
        return json_decode(file_get_contents($this->root . '/' . $filename));
    }

    /**
     * Get the name of the file by log id
     *
     * @param string $id The log id
     *
     * @return ?string
     */
    public function getFileById(
        string $id
    ): ?string {
        // Check that the directory exists
        $this->checkDirExists($this->root);

        // Get all files in the directory and filter to log id
        $files = array_filter(scandir($this->root), function($item) use ($id) {
            if (is_dir($this->root . '/' . $item)) {
                return false;
            }

            $content = $this->extractLogData($item);

            return $content->id == $id;
        });

        return count($files) ? array_values($files)[0] : null;
    }

    /**
     * Get the git branch id
     *
     * @throws DuplicateLogException
     * @throws UncommittedBranchException
     *
     * @return string
     */
    public function getGitBranchId(): string
    {
        // Skip if git control is disabled in the config file
        if (config('version.git', false) === false) {
            return '';
        }

        // Get the current branch id
        $git = trim(shell_exec('git rev-parse ' . shell_exec('git rev-parse --abbrev-ref HEAD')));

        // Error check: Ensure a branch has been created and pushed to remote
        if (!$git) {
            throw new UncommittedBranchException();
        }

        // Error check: Ensure a log can't be created for an existing branch
        if ($this->getLogsByBranchId($git)) {
            throw new DuplicateLogException();
        }

        return $git;
    }

    /**
     * Fetch the initial version number value
     *
     * @param int $level The level to fetch (Major = 0; Minor = 1; Patch = 2)
     *
     * @return string
     */
    protected function getInitialVersionNumber(
        int $level = 2
    ): string {
        return explode('.', $this->initial)[$level];
    }

    /**
     * Fetch all version logs as a json array
     *
     * @return array
     */
    protected function getLogs(): array
    {
        // Check that the directory exists
        $this->checkDirExists($this->root);

        // Fetch all logs for the directory
        $logs = array_filter(scandir($this->root), function($item) {
            return !is_dir($this->root . '/' . $item);
        });

        // Convert the log files into json
        $json = [];
        foreach ($logs as $log) {
            $json[] = json_decode(file_get_contents($this->root . '/' . $log));
        }

        // Currenct timestamp for sorting/ordering
        $timestamp = Carbon::now()->getTimestamp();

        return collect($json)->sortBy(function ($log) use ($timestamp) {
            return $timestamp - $log->timestamp;
        })->reverse()->toArray();
    }

    /**
     * Fetch logs that come under the given branch id
     *
     * @param string $git The git branch hash/id
     *
     * @return array
     */
    public function getLogsByBranchId(
        string $git
    ): array {
        return array_filter($this->getLogs(), function ($log) use ($git) {
            return $log->branch_id == $git;
        });
    }

    /**
     * Get the current/future major version number of the application
     *
     * @param ?string $version The version to increment if fetching future (Major, Minor or Patch)
     *
     * @return int
     */
    public function getMajorVersionNumber(
        ?string $version = null
    ): int {
        $iteration = $this->buildVersionNumber('major');

        switch ($version) {
            case 'major':
                return ($iteration + 1);
                break;
            default:
                return $iteration;
                break;
        }
    }

    /**
     * Get the minor count for the iterated minor version
     *
     * @param array $logs The log entries to iterate through
     * @param int $iteration The current key value of the log array to start from
     *
     * @return int
     */
    protected function getMinorCount(
        array $logs,
        int $iteration
    ): int
    {
        // Flag for whether an iteration to break the minor release has been found
        $skip = false;

        $count = collect(array_splice($logs, $iteration))->filter(function ($loop) use (&$skip) {
            if (in_array($loop->type, ['major'])) {
                $skip = true;
            }

            return !$skip && $loop->type !== 'patch';
        })->count();

        return $count > 0 ? $count : 1;
    }

    /**
     * Get the current/future minor version number of the application
     *
     * @param ?string $version The version to increment if fetching future (Major, Minor or Patch)
     *
     * @return int
     */
    public function getMinorVersionNumber(
        ?string $version = null
    ): int {
        $iteration = $this->buildVersionNumber('minor');

        switch ($version) {
            case 'major':
                return 0;
                break;
            case 'minor':
                return ($iteration + 1);
                break;
            default:
                return $iteration;
                break;
        }
    }

    /**
     * Get the patch count for the iterated minor version
     *
     * @param array $logs The log entries to iterate through
     * @param int $iteration The current key value of the log array to start from
     *
     * @return int
     */
    protected function getPatchCount(
        array $logs,
        int $iteration
    ): int
    {
        // Flag for whether an iteration to break the minor release has been found
        $skip = false;

        $count = collect(array_splice($logs, $iteration))->filter(function ($loop) use (&$skip) {
            if (in_array($loop->type, ['major', 'minor'])) {
                $skip = true;
            }

            return !$skip;
        })->count();

        return $count > 0 ? $count : 1;
    }

    /**
     * Get the formatted patch notes to pass to your frontend
     *
     * @return object
     */
    public function getPatchNotes(): object
    {
        $logs = array_reverse($this->getLogs());

        $major = $this->getMajorVersionNumber();
        $minor = $this->getMinorVersionNumber();
        $patch = $this->getPatchVersionNumber();

        $blocks = (object) [];

        collect($logs)->each(function ($log, $iteration) use ($logs, &$blocks, &$major, &$minor, &$patch) {
            switch ($log->type) {
                case 'major':
                    // Iterate values
                    $major--;
                    $minor = $this->getMinorCount($logs, $iteration);
                    $patch = $this->getPatchCount($logs, $iteration);

                    // Store the data
                    $blocks->{$major} = (object) array_merge(
                        (array) $log,
                        (isset($blocks->{$major}) ? [] : [
                            'notes' => (Object) []
                        ])
                    );

                    break;
                case 'minor':
                    // Iterate values
                    $patch = $this->getPatchCount($logs, $iteration);

                    // Set the major version loop if not already set
                    if (!isset($blocks->{$major})) {
                        $blocks->{$major} = (object) [
                            'notes' => (object) []
                        ];
                    }
                    
                    // Decrement values
                    if (count((array) $blocks->{$major}->notes) > 0) {
                        $minor--;
                    }

                    // Store the data
                    $blocks->{$major}->notes->{$minor} = (object) array_merge(
                        (array) $log,
                        (isset($blocks->{$major}->notes->{$minor}) ? [] : [
                            'notes' => (Object) []
                        ])
                    );

                    break;
                default:
                    // Set the major version loop if not already set
                    if (!isset($blocks->{$major})) {
                        $blocks->{$major} = (object) [
                            'notes' => (object) []
                        ];
                    }

                    // Set the minor version loop if not already set
                    if (!isset($blocks->{$major}->notes->{$minor})) {
                        $blocks->{$major}->notes->{$minor} = (object) [
                            'notes' => (object) []
                        ];
                    }

                    // Decrement values
                    if (count((array) $blocks->{$major}->notes->{$minor}->notes) > 0) {
                        $patch--;
                    }

                    // Store the data
                    $blocks->{$major}->notes->{$minor}->notes->{$patch} = (object) $log;

                    break;
            }
        });

        return $blocks;
    }

    /**
     * Get the current/future patch version number of the application
     *
     * @param ?string $version The version to increment if fetching future (Major, Minor or Patch)
     *
     * @return int
     */
    public function getPatchVersionNumber(
        ?string $version = null
    ): int {
        $iteration = $this->buildVersionNumber('patch');

        switch ($version) {
            case 'major':
                return 0;
                break;
            case 'minor':
                return 0;
                break;
            case 'patch':
                return ($iteration + 1);
                break;
            default:
                return $iteration;
                break;
        }
    }

    /**
     * Get the root where version logs are stored
     *
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * Get the full version number of the current application
     *
     * @param ?string $version The current number to iterate (Major, Minor or Patch)
     *
     * @return string
     */
    public function getVersionNumber(
        ?string $version = null
    ): string {
        return implode('.', [
            $this->getMajorVersionNumber($version),
            $this->getMinorVersionNumber($version),
            $this->getPatchVersionNumber($version)
        ]);
    }

    /**
     * Update the defined log file
     *
     * @param string $id The id of the log file to edit
     *
     * @return void
     */
    public function updateLog(
        string $id
    ): void {
        // Get the log file name
        $filename = $this->getFileById($id);

        // Extract the log data
        $template = $this->extractLogData($filename);

        // Update the template values
        $template->timestamp = Carbon::now()->getTimestamp();

        // Store the template as a new file/log
        file_put_contents(
            $this->root . '/' . $filename,
            json_encode($template, JSON_PRETTY_PRINT)
        );
    }
}
