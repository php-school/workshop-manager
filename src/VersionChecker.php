<?php

namespace PhpSchool\WorkshopManager;

use Github\Client;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;

/**
 * Class VersionChecker
 * @package PhpSchool\WorkshopManager
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
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

    /**
     * @param InstalledWorkshop $workshop
     * @param callable $callback
     * @return mixed
     */
    public function checkForUpdates(InstalledWorkshop $workshop, callable $callback)
    {
        $tags = $this->gitHubClient->api('git')->tags()->all($workshop->getOwner(), $workshop->getRepo());
        $latestTag = end($tags); //last tag may not be the newest - could be a bug
        $version = substr($latestTag['ref'], 10);

        if (version_compare($version, $workshop->getVersion())) {
            return $callback($version, true);
        }

        return $callback($version, false);
    }
}
