<?php

namespace ThomasFielding\Version\Providers;

use Illuminate\Support\ServiceProvider;
use ThomasFielding\Version\Commands\AddVersionLog;
use ThomasFielding\Version\Commands\UpdateVersionLog;
use ThomasFielding\Version\Commands\GetCurrentVersion;

class VersionServiceProvider extends ServiceProvider
{
    /**
     * Register function
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Boot function
     *
     * @return void
     */
    public function boot(): void
    {
        // Booted console commands
        if ($this->app->runningInConsole()) {
            // Generate the version stub files
            $this->publishes($this->publishFiles('src/stubs', base_path('version/stubs')), 'template');
        
            // Registered commands
            $this->commands([
                AddVersionLog::class,
                GetCurrentVersion::class,
                UpdateVersionLog::class
            ]);
        }
    }

    /**
     * Get the base directory for this composer package
     *
     * @return string
     */
    protected function baseDir(): string
    {
        return __DIR__ . '/../../';
    }

    /**
     * Get a list of files within a provided directory of the composer package
     *
     * @param string $directory A string of the directory relative to root
     * @param bool $base Whether to fetch from base or not
     *
     * @return array
     */
    protected function getFiles(
        string $directory,
        bool $base = true
    ): array {
        // Setup global directory
        $this->directory = [];

        // Fetch all migration files
        $base = ($base ? ($this->baseDir() . $directory) : $directory);
        
        foreach ($this->scandirClean($base) as $folder) {
            $this->dirToArray($base, $folder);
        }

        return $this->directory;
    }

    /**
     * Convert a directory to an array
     *
     * @param string $base The base directory to scan from
     * @param string $item The item directory to store into
     *
     * @return void
     */
    protected function dirToArray(
        string $base,
        string $item
    ): void {
        if (is_dir($base . '/' . $item)) {
            foreach ($this->scandirClean($base . '/' . $item) as $dir) {
                $this->dirToArray($base, $item . '/' . $dir);
            }
        }

        if (!is_dir($base . '/' . $item)) {
            $this->directory[] = $item;
        }
    }

    /**
     * Clean a directory
     *
     * @param string $directory The directory to clean
     *
     * @return array
     */
    protected function scandirClean(
        string $directory
    ): array {
        // If the directory to scan does not exist, create it
        if (!is_dir($directory)) {
            $steps = explode('/', $directory);

            foreach ($steps as $key => $step) {
                $glue = implode('/', array_slice($steps, 0, $key + 1));

                if ($glue && !is_dir($glue)) {
                    mkdir($glue);
                }
            }
        }

        // Get an array of folder contents
        $results = scandir($directory);

        // Remove the dot directories for backwards navigation
        unset($results[0]);
        unset($results[1]);

        // Return the results
        return $results;
    }

    /**
     * Create a publishable array of composer > laravel files
     *
     * @param string $composerDir A string of the composer directory relative to root
     * @param string $laravelDir  A string of the laravel directory relative to root
     * @param array  $trim        A 2 value array of how many letters to trim from the front and back of the composer filename
     * @param bool   $prepend     Whether to prepend the laravel file name with a datestamp
     *
     * @return array
     */
    protected function publishFiles(
        string $composerDir,
        string $laravelDir,
        array $trim = [],
        bool $prepend = false
    ): array {
        // Fetch all files
        $created = $this->getFiles($composerDir, true);

        // Get a list of the existing files
        $existing = $this->getFiles($laravelDir, false);

        // File array to be built
        $files = [];

        // Walk through the files array and perform checks
        array_walk($created, function ($value, $key) use (&$files, $existing, $composerDir, $laravelDir, $trim, $prepend) {
            // Check to see if the file has already been exported (or one with a conflicting name)
            $found = array_filter($existing, function($el) use ($value, $trim) {
                return (strpos($el, ($trim ? substr($value, $trim[0], $trim[1]) : $value)) !== false);
            });

            // Add the migration if it does not already exist
            if (!$found) {
                $files[$this->baseDir() . $composerDir . '/' . $value] = ($laravelDir . '/' . ($prepend ? date('Y_m_d_His', (time() + $key)) . '_' : '')  . ($trim ? substr($value, $trim[0], $trim[1]) : $value));
            }
        });

        // Make all of the directories in laravel in order to populate
        $this->makeDirectories($files);

        return $files;
    }

    /**
     * Create the directory structure required to publish the files
     *
     * @param array $files An array of files to work through
     *
     * @return void
     */
    protected function makeDirectories(
        array $files
    ): void {
        foreach ($files as $file) {
            $arr = explode('/', $file);

            unset($arr[count($arr) - 1]);

            $dir = '';

            foreach ($arr as $a) {
                $dir = $dir . '/' . $a;

                if (!is_dir($dir)) {
                    mkdir($dir);
                }
            }
        }
    }
}
