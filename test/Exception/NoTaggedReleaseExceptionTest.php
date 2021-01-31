<?php

declare(strict_types=1);

namespace PhpSchool\WorkshopManagerTest\Exception;

use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\NoTaggedReleaseException;
use PHPUnit\Framework\TestCase;

class NoTaggedReleaseExceptionTest extends TestCase
{
    public function testFromException(): void
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');

        $e = NoTaggedReleaseException::fromWorkshop($workshop);

        $this->assertEquals("Workshop learnyouphp has no tagged releases.", $e->getMessage());
    }
}
