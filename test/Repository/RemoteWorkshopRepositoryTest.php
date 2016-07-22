<?php

namespace PhpSchool\WorkshopManagerTest\Repository;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\RequiresNetworkAccessException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Repository\RemoteWorkshopRepository;
use PHPUnit_Framework_TestCase;

/**
 * Class RemoteWorkshopRepositoryTest
 * @package PhpSchool\WorkshopManagerTest\Repository
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class RemoteWorkshopRepositoryTest extends PHPUnit_Framework_TestCase
{
    public function testExceptionIsThrownIfNoConnection()
    {
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('http://www.not-a-valid-site.org');

        $repo = new RemoteWorkshopRepository($json);
        $this->expectException(RequiresNetworkAccessException::class);
        $repo->getByName('workshop');
    }

    public function testGetByNameThrowsExceptionIfWorkshopNotExist()
    {
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->will(
                $this->returnValue(
                    [
                        'workshops' => []
                    ]
                )
            );

        $json
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('http://www.google.com');

        $repo = new RemoteWorkshopRepository($json);
        $this->expectException(WorkshopNotFoundException::class);
        $repo->getByName('nope');
    }

    public function testGetByName()
    {
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->will(
                $this->returnValue(
                    [
                        'workshops' => [
                            [
                                'name' => 'workshop',
                                'display_name' => 'workshop',
                                'owner' => 'aydin',
                                'repo' => 'repo',
                                'description' => 'workshop',
                            ]
                        ]
                    ]
                )
            );

        $json
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('http://www.google.com');

        $repo = new RemoteWorkshopRepository($json);
        $workshop = $repo->getByName('workshop');
        $this->assertInstanceOf(Workshop::class, $workshop);
        $this->assertEquals('workshop', $workshop->getName());
        $this->assertEquals('workshop', $workshop->getDisplayName());
        $this->assertEquals('aydin', $workshop->getOwner());
        $this->assertEquals('repo', $workshop->getRepo());
        $this->assertEquals('workshop', $workshop->getDescription());
    }

    public function testFind()
    {
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->will(
                $this->returnValue(
                    [
                        'workshops' => [
                            [
                                'name' => 'workshop',
                                'display_name' => 'learn-you-php',
                                'owner' => 'aydin',
                                'repo' => 'repo',
                                'description' => 'workshop',
                            ]
                        ]
                    ]
                )
            );

        $json
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('http://www.google.com');

        $repo = new RemoteWorkshopRepository($json);

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
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->will(
                $this->returnValue(
                    [
                        'workshops' => [
                            [
                                'name' => 'learnyouphp',
                                'display_name' => 'Learn you PHP',
                                'owner' => 'aydin',
                                'repo' => 'repo',
                                'description' => 'A workshop'
                            ],
                            [
                                'name' => 'php7',
                                'display_name' => 'Learn PHP7',
                                'owner' => 'aydin',
                                'repo' => 'repo',
                                'description' => 'A workshop'
                            ]
                        ]
                    ]
                )
            );

        $json
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('http://www.google.com');

        $repo = new RemoteWorkshopRepository($json);

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