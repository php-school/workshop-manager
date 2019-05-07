<?php

namespace PhpSchool\WorkshopManagerTest;

use PhpSchool\WorkshopManager\InstallResult;
use PHPUnit\Framework\TestCase;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class InstallResultTest extends TestCase
{
    public function testGetters()
    {
        $result = new InstallResult(0, '');

        $this->assertEquals(0, $result->getExitCode());
        $this->assertEquals('', $result->getOutput());
        $this->assertFalse($result->missingExtensions());
        $this->assertEmpty($result->getMissingExtensions());
    }

    public function testMissingExtensionsAreParsed()
    {
        $output  = "the requested PHP extension mbstring is missing from your system\n";
        $output .= "the requested PHP extension zip is missing from your system\n";

        $result = new InstallResult(1, $output);

        $this->assertEquals(1, $result->getExitCode());
        $this->assertEquals($output, $result->getOutput());
        $this->assertTrue($result->missingExtensions());
        $this->assertSame(['mbstring', 'zip'], $result->getMissingExtensions());
    }

    public function testMissingExtensionsDupesAreRemoved()
    {
        $output  = "the requested PHP extension mbstring is missing from your system\n";
        $output .= "the requested PHP extension mbstring is missing from your system\n";

        $result = new InstallResult(1, $output);

        $this->assertEquals(1, $result->getExitCode());
        $this->assertEquals($output, $result->getOutput());
        $this->assertTrue($result->missingExtensions());
        $this->assertSame(['mbstring'], $result->getMissingExtensions());
    }
}
