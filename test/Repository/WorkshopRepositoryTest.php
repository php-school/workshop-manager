<?php

namespace PhpSchool\WorkshopManagerTest\Repository;

use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use PHPUnit_Framework_TestCase;

/**
 * Class WorkshopRepositoryTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class WorkshopRepositoryTest extends PHPUnit_Framework_TestCase
{
    public function test1()
    {
        $this->assertTrue(true);
    }

    public function testGetByNameThrowsExceptionIfWorkshopNotExist()
    {
        $repo = new WorkshopRepository();
        $this->expectException(WorkshopNotFoundException::class);
        $repo->getByName('nope');
    }

    public function testGetByName()
    {
        $workshop = new Workshop('workshop', 'workshop', 'aydin', 'repo', 'workshop');
        $repo = new WorkshopRepository([$workshop]);
        $this->assertEquals($workshop, $repo->getByName('workshop'));
    }

    public function testHasWorkshop()
    {
        $workshop = new Workshop('workshop', 'workshop', 'aydin', 'repo', 'workshop');
        $repo = new WorkshopRepository([$workshop]);
        $this->assertTrue($repo->hasWorkshop('workshop'));


        $repo = new WorkshopRepository();
        $this->assertFalse($repo->hasWorkshop('workshop'));
    }

    public function testFind()
    {
        $workshop = new Workshop('workshop', 'learn-you-php', 'aydin', 'repo', 'workshop');
        $repo = new WorkshopRepository([$workshop]);

        $this->assertCount(1, $repo->find('workshop'));
        $this->assertCount(1, $repo->find('worksh'));
        $this->assertCount(1, $repo->find('wo'));
        $this->assertCount(1, $repo->find('leart-yof-phf'));
        $this->assertCount(1, $repo->find('learn-you-plf'));
        $this->assertCount(0, $repo->find('leart-yof-pff')); //spelt too many characters wrong
        $this->assertCount(0, $repo->find('not-a-workshop')); //spelt too many characters wrong
    }

    public function testFindWithMultipleWorkshops()
    {
        $workshop1 = new Workshop('learnyouphp', 'Learn you PHP', 'aydin', 'repo', 'A workshop');
        $workshop2 = new Workshop('php7', 'Learn PHP7', 'aydin', 'repo', 'A workshop');
        $repo = new WorkshopRepository([$workshop1, $workshop2]);

        $this->assertCount(2, $repo->find('learn'));
        $this->assertCount(2, $repo->find('php'));

        $this->assertCount(1, $repo->find('learnyouphp'));
        $this->assertCount(1, $repo->find('learnyoutfg'));

        $this->assertCount(1, $repo->find('php7'));
        $this->assertCount(1, $repo->find('php6'));
        $this->assertCount(1, $repo->find('fff7'));

        $this->assertCount(0, $repo->find('not-a-workshop')); //spelt too many characters wrong
    }
}
