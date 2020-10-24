<?php

namespace PhpSchool\WorkshopManagerTest\Entity;

use PhpSchool\WorkshopManager\Entity\Workshop;
use PHPUnit\Framework\TestCase;

class WorkshopTest extends TestCase
{
    public function testGetters(): void
    {
        $workshop = new Workshop('workshop', 'workshop', 'aydin', 'repo', 'workshop', 'core');
        $this->assertEquals('workshop', $workshop->getCode());
        $this->assertEquals('workshop', $workshop->getDisplayName());
        $this->assertEquals('aydin', $workshop->getGitHubOwner());
        $this->assertEquals('repo', $workshop->getGitHubRepoName());
        $this->assertEquals('workshop', $workshop->getDescription());
        $this->assertEquals('core', $workshop->getType());
    }
}
