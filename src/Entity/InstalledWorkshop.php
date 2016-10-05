<?php

namespace PhpSchool\WorkshopManager\Entity;

/**
 * @package PhpSchool\WorkshopManager\Entity
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
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
     * @param string $level
     */
    public function __construct($code, $displayName, $gitHubOwner, $gitHubRepoName, $description, $type, $level, $version)
    {
        $this->code = $code;
        $this->displayName = $displayName;
        $this->gitHubOwner = $gitHubOwner;
        $this->gitHubRepoName = $gitHubRepoName;
        $this->description = $description;
        $this->type = $type;
        $this->level = $level;
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
            $workshop->getLevel(),
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
