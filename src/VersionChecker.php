<?php

namespace PhpSchool\WorkshopManager;

use Github\Client;
use Github\Exception\ExceptionInterface;
use PhpSchool\WorkshopManager\Entity\Release;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\RequiresNetworkAccessException;
use RuntimeException;

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
            $tags = collect($this->gitHubClient->api('git')->tags()->all(
                $workshop->getGitHubOwner(),
                $workshop->getGitHubRepoName()
            ));
        } catch (ExceptionInterface $e) {
            throw new RequiresNetworkAccessException('Cannot communicate with GitHub - check your internet connection');
        }

        if ($tags->isEmpty()) {
            throw new RuntimeException('This workshop has no tagged releases.');
        }

        $tags = $tags
            ->keyBy(function ($tag) {
                return $tag['object']['sha'];
            })
            ->map(function ($tag) {
                return substr($tag['ref'], 10);
            });

        /** @noinspection PhpUndefinedMethodInspection */
        $latestVersion = $tags->reduce(function ($highest, $current) {
            return version_compare($highest, $current, '>') ? $highest : $current;
        });

        /** @noinspection PhpUndefinedMethodInspection */
        return new Release($latestVersion, $tags->search($latestVersion));
    }
}
