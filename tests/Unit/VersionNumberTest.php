<?php

namespace ThomasFielding\Version\Tests\Feature;

use ThomasFielding\Version\Tests\TestCase;
use ThomasFielding\Version\Services\VersionService;

class VersionNumberTest extends TestCase
{
    /** @test */
    public function version_number_patch_working_as_expected()
    {
        // Setup
        $versionService = new VersionService(dirname(__FILE__) . '/../Stubs/patch', '0.0.0', []);

        // Mock
        $response = $versionService->getVersionNumber();

        // Assert
        $this->assertEquals('0.0.3', $response);
    }

    /** @test */
    public function version_number_minor_working_as_expected()
    {
        // Setup
        $versionService = new VersionService(dirname(__FILE__) . '/../Stubs/minor', '0.0.0', []);

        // Mock
        $response = $versionService->getVersionNumber();

        // Assert
        $this->assertEquals('0.1.1', $response);
    }

    /** @test */
    public function version_number_major_working_as_expected()
    {
        // Setup
        $versionService = new VersionService(dirname(__FILE__) . '/../Stubs/major', '0.0.0', []);

        // Mock
        $response = $versionService->getVersionNumber();

        // Assert
        $this->assertEquals('1.2.0', $response);
    }

    /** @test */
    public function version_number_set_starting_point_working_as_expected()
    {
        // Setup
        $versionService = new VersionService(dirname(__FILE__) . '/../Stubs/major', '3.22.4', []);

        // Mock
        $response = $versionService->getVersionNumber();

        // Assert
        $this->assertEquals('4.2.0', $response);
    }
}
