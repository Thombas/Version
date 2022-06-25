<?php

namespace ThomasFielding\Version\Tests\Unit;

use ThomasFielding\Version\Tests\TestCase;
use ThomasFielding\Version\Services\VersionService;

class GenerateLogTest extends TestCase
{
    /** @test */
    public function can_generate_a_patch_log()
    {
        // Setup
        $versionService = new VersionService(dirname(__FILE__) . '/../Stubs/tmp', '0.0.0', []);

        // Mock
        $response = $versionService->createLog('', 'patch', null, null, null);

        // Assert
        $this->assertFileExists(dirname(__FILE__) . '/../Stubs/tmp/' . $response);
    }

    /** @test */
    public function can_generate_a_minor_log()
    {
        // Setup
        $versionService = new VersionService(dirname(__FILE__) . '/../Stubs/tmp', '0.0.0', []);

        // Mock
        $response = $versionService->createLog('', 'minor', null, null, null);

        // Assert
        $this->assertFileExists(dirname(__FILE__) . '/../Stubs/tmp/' . $response);
    }

    /** @test */
    public function can_generate_a_major_log()
    {
        // Setup
        $versionService = new VersionService(dirname(__FILE__) . '/../Stubs/tmp', '0.0.0', []);

        // Mock
        $response = $versionService->createLog('', 'major', null, null, null);

        // Assert
        $this->assertFileExists(dirname(__FILE__) . '/../Stubs/tmp/' . $response);
    }
}
