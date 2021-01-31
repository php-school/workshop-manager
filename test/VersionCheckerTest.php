<?php

namespace PhpSchool\WorkshopManagerTest;

use PhpSchool\WorkshopManager\Exception\NoTaggedReleaseException;
use PhpSchool\WorkshopManager\GitHubApi\Client;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\GitHubApi\Exception;
use PhpSchool\WorkshopManager\VersionChecker;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class VersionCheckerTest extends TestCase
{
    public function testGetLatestReleaseThrowsExceptionIfApiThrowsException(): void
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $client = $this->createMock(Client::class);

        $client
            ->expects($this->once())
            ->method('tags')
            ->with($workshop->getGitHubOwner(), $workshop->getGitHubRepoName())
            ->willThrowException(new Exception());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot communicate with GitHub - check your internet connection');

        $versionChecker = new VersionChecker($client);
        $versionChecker->getLatestRelease($workshop);
    }

    public function testGetLatestReleaseThrowsExceptionIfNoTags(): void
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $client = $this->createMock(Client::class);

        $client
            ->expects($this->once())
            ->method('tags')
            ->with($workshop->getGitHubOwner(), $workshop->getGitHubRepoName())
            ->willReturn([]);

        $this->expectException(NoTaggedReleaseException::class);
        $this->expectExceptionMessage("Workshop {$workshop->getDisplayName()} has no tagged releases.");

        $versionChecker = new VersionChecker($client);
        $versionChecker->getLatestRelease($workshop);
    }

    public function testGetLatestReleaseThrowsExceptionIfCannotFindTag(): void
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $client = $this->createMock(Client::class);

        $client
            ->expects($this->once())
            ->method('tags')
            ->with($workshop->getGitHubOwner(), $workshop->getGitHubRepoName())
            ->willThrowException(new Exception('Not Found'));

        $this->expectException(NoTaggedReleaseException::class);
        $this->expectExceptionMessage("Workshop {$workshop->getDisplayName()} has no tagged releases.");

        $versionChecker = new VersionChecker($client);
        $versionChecker->getLatestRelease($workshop);
    }

    public function testGetLatestRelease(): void
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $client = $this->createMock(Client::class);

        $client
            ->expects($this->once())
            ->method('tags')
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
