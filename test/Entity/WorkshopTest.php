<?php

namespace PhpSchool\WorkshopManagerTest\Entity;

use PhpSchool\WorkshopManager\Entity\Workshop;
use PHPUnit_Framework_TestCase;

/**
 * Class WorkshopTest
 * @package PhpSchool\WorkshopManagerTest\Entity
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class WorkshopTest extends PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $workshop = new Workshop('workshop', 'workshop', 'aydin', 'repo', 'workshop');
        $this->assertEquals('workshop', $workshop->getName());
        $this->assertEquals('workshop', $workshop->getDisplayName());
        $this->assertEquals('aydin', $workshop->getOwner());
        $this->assertEquals('repo', $workshop->getRepo());
        $this->assertEquals('workshop', $workshop->getDescription());
    }
}
