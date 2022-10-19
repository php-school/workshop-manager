<?php

namespace PhpSchool\WorkshopManagerTest;

use PhpSchool\WorkshopManager\InstallResult;
use PHPUnit\Framework\TestCase;

class InstallResultTest extends TestCase
{
    public function testGetters(): void
    {
        $result = new InstallResult(0, '');

        $this->assertEquals(0, $result->getExitCode());
        $this->assertEquals('', $result->getOutput());
        $this->assertFalse($result->missingExtensions());
        $this->assertEmpty($result->getMissingExtensions());
    }

    public function testMissingExtensionsAreParsed(): void
    {
        $output  = "the requested PHP extension mbstring is missing from your system\n";
        $output .= "the requested PHP extension zip is missing from your system\n";

        $result = new InstallResult(1, $output);

        $this->assertEquals(1, $result->getExitCode());
        $this->assertEquals($output, $result->getOutput());
        $this->assertTrue($result->missingExtensions());
        $this->assertSame(['mbstring', 'zip'], $result->getMissingExtensions());
    }

    public function testMissingExtensionsDupesAreRemoved(): void
    {
        $output  = "the requested PHP extension mbstring is missing from your system\n";
        $output .= "the requested PHP extension mbstring is missing from your system\n";

        $result = new InstallResult(1, $output);

        $this->assertEquals(1, $result->getExitCode());
        $this->assertEquals($output, $result->getOutput());
        $this->assertTrue($result->missingExtensions());
        $this->assertSame(['mbstring'], $result->getMissingExtensions());
    }

    public function testWhenResolveError(): void
    {
        $output = 'Your requirements could not be resolved to an installable set of packages.';

        $result = new InstallResult(1, $output);

        $this->assertEquals(1, $result->getExitCode());
        $this->assertEquals($output, $result->getOutput());
        $this->assertFalse($result->missingExtensions());
        $this->assertTrue($result->couldNotBeResolved());
    }
}
