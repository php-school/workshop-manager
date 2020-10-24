<?php

namespace PhpSchool\WorkshopManagerTest\Entity;

use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PHPUnit\Framework\TestCase;

class InstalledWorkshopTest extends TestCase
{
    public function testGetters(): void
    {
        $workshop = new InstalledWorkshop('workshop', 'workshop', 'aydin', 'repo', 'workshop', 'core', '1.0.0');
        $this->assertEquals('workshop', $workshop->getCode());
        $this->assertEquals('workshop', $workshop->getDisplayName());
        $this->assertEquals('aydin', $workshop->getGitHubOwner());
        $this->assertEquals('repo', $workshop->getGitHubRepoName());
        $this->assertEquals('workshop', $workshop->getDescription());
        $this->assertEquals('core', $workshop->getType());
        $this->assertEquals('1.0.0', $workshop->getVersion());
    }

    public function testFromWorkshop(): void
    {
        $workshop = new Workshop('workshop', 'workshop', 'aydin', 'repo', 'workshop', 'core');
        $installed = InstalledWorkshop::fromWorkshop($workshop, '1.0.0');

        $this->assertEquals('workshop', $installed->getCode());
        $this->assertEquals('workshop', $installed->getDisplayName());
        $this->assertEquals('aydin', $installed->getGitHubOwner());
        $this->assertEquals('repo', $installed->getGitHubRepoName());
        $this->assertEquals('workshop', $installed->getDescription());
        $this->assertEquals('core', $workshop->getType());
        $this->assertEquals('1.0.0', $installed->getVersion());
    }
}
