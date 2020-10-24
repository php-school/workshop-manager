<?php

namespace PhpSchool\WorkshopManagerTest\Repository;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use PHPUnit\Framework\TestCase;

class InstalledWorkshopRepositoryTest extends TestCase
{
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

        $repo = new InstalledWorkshopRepository($json);
        $this->expectException(WorkshopNotFoundException::class);
        $repo->getByCode('nope');
    }

    public function testGetByCode(): void
    {
        $repo = $this->getRepo();
        $workshop = $repo->getByCode('workshop');
        $this->assertInstanceOf(InstalledWorkshop::class, $workshop);
        $this->assertEquals('workshop', $workshop->getCode());
        $this->assertEquals('workshop', $workshop->getDisplayName());
        $this->assertEquals('aydin', $workshop->getGitHubOwner());
        $this->assertEquals('repo', $workshop->getGitHubRepoName());
        $this->assertEquals('workshop', $workshop->getDescription());
        $this->assertEquals('1.0.0', $workshop->getVersion());
    }

    public function testHasWorkshop(): void
    {
        $repo = $this->getRepo();
        $this->assertTrue($repo->hasWorkshop('workshop'));


        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->willReturn(
                [
                    'workshops' => []
                ]
            );

        $repo = new InstalledWorkshopRepository($json);
        $this->assertFalse($repo->hasWorkshop('workshop'));
    }

    public function testGetAllWorkshops(): void
    {
        $repo = $this->getRepo();
        $this->assertCount(1, $repo->getAll());
    }

    public function testIsEmpty(): void
    {
        $repo = $this->getRepo();
        $this->assertFalse($repo->isEmpty());

        $repo->remove($repo->getByCode('workshop'));
        $this->assertTrue($repo->isEmpty());
    }

    public function testExceptionIsThrowIfTryingToRemoveNonExistingWorkshop(): void
    {
        $repo = $this->getRepo();
        $this->expectException(WorkshopNotFoundException::class);

        $workshop = new InstalledWorkshop('remove-me', 'workshop', 'aydin', 'repo', 'workshop', 'core', '1.0.0');
        $repo->remove($workshop);
    }

    public function testRemove(): void
    {
        $repo = $this->getRepo();
        $this->assertCount(1, $repo->getAll());

        $workshop = $repo->getByCode('workshop');
        $repo->remove($workshop);
        $this->assertCount(0, $repo->getAll());
    }

    public function testAdd(): void
    {
        $repo = $this->getRepo([]);
        $workshop = new InstalledWorkshop('workshop', 'workshop', 'aydin', 'repo', 'workshop', 'core', '1.0.0');

        $this->assertCount(0, $repo->getAll());
        $repo->add($workshop);
        $this->assertCount(1, $repo->getAll());
    }

    public function testSave(): void
    {
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->willReturn(['workshops' => []]);

        $repo = new InstalledWorkshopRepository($json);
        $repo->add(new InstalledWorkshop('workshop', 'workshop', 'aydin', 'repo', 'workshop', 'core', '1.0.0'));

        $data = [
            'workshops' => [
                [
                    'workshop_code' => 'workshop',
                    'display_name' => 'workshop',
                    'github_owner' => 'aydin',
                    'github_repo_name' => 'repo',
                    'description' => 'workshop',
                    'type' => 'core',
                    'version' => '1.0.0'
                ]
            ]
        ];

        $json
            ->expects($this->once())
            ->method('write')
            ->with($data);

        $repo->save();
    }

    private function getRepo(array $workshops = null): InstalledWorkshopRepository
    {
        if (null === $workshops) {
            $workshops = [
                [
                    'workshop_code' => 'workshop',
                    'display_name' => 'workshop',
                    'github_owner' => 'aydin',
                    'github_repo_name' => 'repo',
                    'description' => 'workshop',
                    'type' => 'core',
                    'version' => '1.0.0'
                ]
            ];
        }

        $data = [
            'workshops' => $workshops
        ];


        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->willReturn($data);

        return new InstalledWorkshopRepository($json);
    }
}
