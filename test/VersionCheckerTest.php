<?php

namespace PhpSchool\WorkshopManagerTest;

use Github\Api\GitData;
use Github\Api\GitData\Tags;
use Github\Client;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Entity\Release;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\VersionChecker;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class VersionCheckerTest extends TestCase
{
    public function testGetLatestReleaseThrowsExceptionIfApiThrowsException()
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $client = $this->createMock(Client::class);

        $gitData = $this->createMock(GitData::class);
        $tags = $this->createMock(Tags::class);

        $client
            ->expects($this->any())
            ->method('api')
            ->with('git')
            ->willReturn($gitData);

        $gitData
            ->expects($this->any())
            ->method('tags')
            ->willReturn($tags);

        $tags
            ->expects($this->once())
            ->method('all')
            ->with($workshop->getGitHubOwner(), $workshop->getGitHubRepoName())
            ->willThrowException(new \Github\Exception\RuntimeException);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot communicate with GitHub - check your internet connection');

        $versionChecker = new VersionChecker($client);
        $versionChecker->getLatestRelease($workshop);
    }

    public function testGetLatestReleaseThrowsExceptionIfNoTags()
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $client = $this->createMock(Client::class);

        $gitData = $this->createMock(GitData::class);
        $tags = $this->createMock(Tags::class);

        $client
            ->expects($this->any())
            ->method('api')
            ->with('git')
            ->willReturn($gitData);

        $gitData
            ->expects($this->any())
            ->method('tags')
            ->willReturn($tags);

        $tags
            ->expects($this->once())
            ->method('all')
            ->with($workshop->getGitHubOwner(), $workshop->getGitHubRepoName())
            ->willReturn([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This workshop has no tagged releases.');

        $versionChecker = new VersionChecker($client);
        $versionChecker->getLatestRelease($workshop);
    }

    public function testGetLatestRelease()
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $client = $this->createMock(Client::class);

        $gitData = $this->createMock(GitData::class);
        $tags = $this->createMock(Tags::class);

        $client
            ->expects($this->any())
            ->method('api')
            ->with('git')
            ->willReturn($gitData);

        $gitData
            ->expects($this->any())
            ->method('tags')
            ->willReturn($tags);

        $tags
            ->expects($this->once())
            ->method('all')
            ->with($workshop->getGitHubOwner(), $workshop->getGitHubRepoName())
            ->willReturn(
                [
                    [
                        'ref' => 'refs/tags/1.0.0',
                        'object' => [
                            'sha' => 'AAAA'
                        ]
                    ],
                    [
                        'ref' => 'refs/tags/2.0.0',
                        'object' => [
                            'sha' => 'BBBB'
                        ]
                    ],
                    [
                        'ref' => 'refs/tags/1.5.0',
                        'object' => [
                            'sha' => 'CCCC'
                        ]
                    ]
                ]
            );

        $versionChecker = new VersionChecker($client);
        $release = $versionChecker->getLatestRelease($workshop);
        $this->assertEquals('2.0.0', $release->getTag());
        $this->assertEquals('BBBB', $release->getSha());
    }
}
