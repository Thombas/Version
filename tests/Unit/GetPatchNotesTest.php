<?php

namespace ThomasFielding\Version\Tests\Unit;

use ThomasFielding\Version\Tests\TestCase;
use ThomasFielding\Version\Services\VersionService;

class GetPatchNotesTest extends TestCase
{
    /** @test */
    public function can_format_patch_notes_correctly()
    {
        // Setup
        $versionService = new VersionService(dirname(__FILE__) . '/../Stubs/major', '0.0.0', []);

        // Mock
        $response = $versionService->getPatchNotes();

        // Assert
        $this->assertTrue(isset($response->{1}->notes->{1}));
        $this->assertFalse(isset($response->{1}->notes->{1}->notes->{0}));
        $this->assertTrue(isset($response->{0}->notes->{0}->notes->{1}));
        $this->assertFalse(isset($response->{0}->notes->{0}->notes->{0}));
    }
}
