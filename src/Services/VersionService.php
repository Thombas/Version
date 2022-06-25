<?php

namespace ThomasFielding\Version\Services;

class VersionService
{
    /** @var */
    protected string $root;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->root = './version';
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
     * Get the name of the file by log id
     *
     * @param string $id The log id
     *
     * @return ?string
     */
    public function getFileById(
        string $id
    ): ?string {
        $files = array_filter(scandir($this->root), function($item) use ($id) {
            if (is_dir($this->root . '/' . $item)) {
                return false;
            }

            $content = json_decode(file_get_contents($this->root . '/' . $item));

            return $content->id == $id;
        });

        return count($files) ? array_values($files)[0] : null;
    }

    /**
     * Fetch all version logs as a json array
     *
     * @return array
     */
    protected function getLogs(): array
    {
        $logs = array_filter(scandir($this->root), function($item) {
            return !is_dir($this->root . '/' . $item);
        });

        $json = [];

        foreach ($logs as $log) {
            $json[] = json_decode(file_get_contents($this->root . '/' . $log));
        }

        return $json;
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

        $major = 0;
        $minor = 0;
        $patch = 0;

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
}
