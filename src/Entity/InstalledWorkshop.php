<?php

namespace PhpSchool\WorkshopManager\Entity;

final class InstalledWorkshop extends Workshop
{
    /**
     * @var string
     */
    private $version;

    /**
     * @param string $code
     * @param string $displayName
     * @param string $gitHubOwner
     * @param string $gitHubRepoName
     * @param string $description
     * @param string $type
     * @param string $version
     */
    public function __construct($code, $displayName, $gitHubOwner, $gitHubRepoName, $description, $type, $version)
    {
        $this->code = $code;
        $this->displayName = $displayName;
        $this->gitHubOwner = $gitHubOwner;
        $this->gitHubRepoName = $gitHubRepoName;
        $this->description = $description;
        $this->type = $type;
        $this->version = $version;
    }

    /**
     * @param Workshop $workshop
     * @param string $version
     * @return static
     */
    public static function fromWorkshop(Workshop $workshop, $version)
    {
        return new static(
            $workshop->getCode(),
            $workshop->getDisplayName(),
            $workshop->getGitHubOwner(),
            $workshop->getGitHubRepoName(),
            $workshop->getDescription(),
            $workshop->getType(),
            $version
        );
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
