<?php

namespace PhpSchool\WorkshopManager;

use PhpSchool\WorkshopManager\Entity\Release;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\RequiresNetworkAccessException;
use PhpSchool\WorkshopManager\GitHubApi\Client;
use PhpSchool\WorkshopManager\GitHubApi\Exception;
use RuntimeException;
use PhpSchool\WorkshopManager\Util\Collection;

class VersionChecker
{
    /**
     * @var Client
     */
    private $gitHubClient;

    public function __construct(Client $gitHubClient)
    {
        $this->gitHubClient = $gitHubClient;
    }

    public function getLatestRelease(Workshop $workshop): Release
    {
        try {
            /** @var Collection<array{object: array{sha: string}, ref: string}> $tags */
            $tags = collect($this->gitHubClient->tags(
                $workshop->getGitHubOwner(),
                $workshop->getGitHubRepoName()
            ));
        } catch (Exception $e) {
            throw new RequiresNetworkAccessException('Cannot communicate with GitHub - check your internet connection');
        }

        if ($tags->isEmpty()) {
            throw new RuntimeException('This workshop has no tagged releases.');
        }

        /** @var array{sha: string, ref: string} $latestVersion */
        $latestVersion = $tags
            ->map(function ($tag) {
                return [
                    'sha' => $tag['object']['sha'],
                    'ref' => substr($tag['ref'], 10)
                ];
            })
            ->sortBy(function (array $a, array $b) {
                return version_compare($b['ref'], $a['ref']);
            })
            ->first();

        return new Release($latestVersion['ref'], $latestVersion['sha']);
    }
}
