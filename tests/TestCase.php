<?php

namespace ThomasFielding\Version\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    /**
     * Set up/prepare for testing
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->clearFiles();
    }

    /**
     * Tear down/clean up after testing
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::setUp();

        $this->clearFiles();
    }

    /**
     * Delete all files in the tmp directory used for testing
     *
     * @return void
     */
    protected function clearFiles(): void
    {
        $files = glob(dirname(__FILE__) . '/Stubs/tmp/{,.}*', GLOB_BRACE);

        foreach($files as $file){ // iterate files
            if (is_file($file)) {
                unlink($file); // delete file
            }
        }
    }
}
