<?php

namespace PhpSchool\WorkshopManagerTest\Entity;

use PhpSchool\WorkshopManager\Entity\Release;
use PHPUnit\Framework\TestCase;

class ReleaseTest extends TestCase
{
    public function testGetters(): void
    {
        $release = new Release('1.0.0', 'AAAA');
        $this->assertEquals('1.0.0', $release->getTag());
        $this->assertEquals('AAAA', $release->getSha());
    }
}
