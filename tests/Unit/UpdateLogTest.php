<?php

namespace ThomasFielding\Version\Tests\Unit;

use ThomasFielding\Version\Tests\TestCase;
use ThomasFielding\Version\Services\VersionService;

class UpdateLogTest extends TestCase
{
    /** @test */
    public function can_update_the_timestamp_of_a_log_file()
    {
        // Setup
        $versionService = new VersionService(dirname(__FILE__) . '/../Stubs/tmp', '0.0.0', []);
        $filename = $versionService->createLog('', 'patch', null, null);
        $data = json_decode(
            file_get_contents(dirname(__FILE__) . '/../Stubs/tmp/' . $filename)
        );

        // Mock
        sleep(1);
        $versionService->updateLog($data->id);
        $response = json_decode(
            file_get_contents(dirname(__FILE__) . '/../Stubs/tmp/' . $filename)
        );

        // Assert
        $this->assertNotEmpty($data->timestamp);
        $this->assertNotEquals($data->timestamp, $response->timestamp);
    }
}
