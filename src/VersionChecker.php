<?php

namespace PhpSchool\WorkshopManager;

use Github\Client;
use Github\Exception\ExceptionInterface;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Entity\Release;
use PhpSchool\WorkshopManager\Entity\Workshop;
use RuntimeException;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class VersionChecker
{
    /**
     * @var Client
     */
    private $gitHubClient;

    /**
     * @param Client $gitHubClient
     */
    public function __construct(Client $gitHubClient)
    {
        $this->gitHubClient = $gitHubClient;
    }

    /**
     * @param Workshop $workshop
     * @return Release
     * @throws RuntimeException
     */
    public function getLatestRelease(Workshop $workshop)
    {
        try {
            $tags = collect($this->gitHubClient->api('git')->tags()->all($workshop->getOwner(), $workshop->getRepo()));
        } catch (ExceptionInterface $e) {
            throw new RuntimeException('Cannot communicate with GitHub - check your internet connection');
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

        $latestVersion = $tags->reduce(function ($highest, $current) {
            return version_compare($highest, $current, '>') ? $highest : $current;
        });

        return new Release($latestVersion, $tags->search($latestVersion));
    }

    /**
     * @param InstalledWorkshop $workshop
     * @param callable $callback
     * @return mixed
     */
    public function checkForUpdates(InstalledWorkshop $workshop, callable $callback)
    {
        $latestVersion = $this->getLatestRelease($workshop);

        if (version_compare($latestVersion->getTag(), $workshop->getVersion())) {
            return $callback($latestVersion, true);
        }

        return $callback($latestVersion, false);
    }
}
