<?php

namespace PhpSchool\WorkshopManagerTest\Repository;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\RequiresNetworkAccessException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Repository\RemoteWorkshopRepository;
use PHPUnit\Framework\TestCase;

class RemoteWorkshopRepositoryTest extends TestCase
{
    public function testExceptionIsThrownIfNoConnection(): void
    {
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('http://www.not-a-valid-site.org');

        $repo = new RemoteWorkshopRepository($json);
        $this->expectException(RequiresNetworkAccessException::class);
        $repo->getByCode('workshop');
    }

    public function testGetByNameThrowsExceptionIfWorkshopNotExist(): void
    {
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->willReturn(
                [
                    'workshops' => []
                ]
            );

        $json
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('http://www.google.com');

        $repo = new RemoteWorkshopRepository($json);
        $this->expectException(WorkshopNotFoundException::class);
        $repo->getByCode('nope');
    }

    public function testGetByName(): void
    {
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->willReturn(
                [
                    'workshops' => [
                        [
                            'workshop_code'    => 'workshop',
                            'display_name'     => 'workshop',
                            'github_owner'     => 'aydin',
                            'github_repo_name' => 'repo',
                            'description'      => 'workshop',
                            'type'             => 'core',
                        ]
                    ]
                ]
            );

        $json
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('http://www.google.com');

        $repo = new RemoteWorkshopRepository($json);
        $workshop = $repo->getByCode('workshop');
        $this->assertInstanceOf(Workshop::class, $workshop);
        $this->assertEquals('workshop', $workshop->getCode());
        $this->assertEquals('workshop', $workshop->getDisplayName());
        $this->assertEquals('aydin', $workshop->getGitHubOwner());
        $this->assertEquals('repo', $workshop->getGitHubRepoName());
        $this->assertEquals('workshop', $workshop->getDescription());
    }

    public function testFind(): void
    {
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->willReturn(
                [
                    'workshops' => [
                        [
                            'workshop_code'    => 'workshop',
                            'display_name'     => 'learn-you-php',
                            'github_owner'     => 'aydin',
                            'github_repo_name' => 'repo',
                            'description'      => 'workshop',
                            'type'             => 'core'
                        ]
                    ]
                ]
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

    public function testFindWithMultipleWorkshops(): void
    {
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->willReturn(
                [
                    'workshops' => [
                        [
                            'workshop_code'    => 'learnyouphp',
                            'display_name'     => 'Learn you PHP',
                            'github_owner'     => 'aydin',
                            'github_repo_name' => 'repo',
                            'description'      => 'A workshop',
                            'type'             => 'core',
                        ],
                        [
                            'workshop_code'    => 'php7',
                            'display_name'     => 'Learn PHP7',
                            'github_owner'     => 'aydin',
                            'github_repo_name' => 'repo',
                            'description'      => 'A workshop',
                            'type'             => 'core',
                        ]
                    ]
                ]
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

    public function testAllReturnsAllWorkshops(): void
    {
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->willReturn(
                [
                    'workshops' => [
                        [
                            'workshop_code'    => 'learnyouphp',
                            'display_name'     => 'Learn you PHP',
                            'github_owner'     => 'aydin',
                            'github_repo_name' => 'repo',
                            'description'      => 'A workshop',
                            'type'             => 'core',
                        ],
                        [
                            'workshop_code'    => 'php7',
                            'display_name'     => 'Learn PHP7',
                            'github_owner'     => 'aydin',
                            'github_repo_name' => 'repo',
                            'description'      => 'A workshop',
                            'type'             => 'core',
                        ]
                    ]
                ]
            );

        $json
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('http://www.google.com');

        $repo = new RemoteWorkshopRepository($json);

        $this->assertCount(2, $repo->all());
    }
}
