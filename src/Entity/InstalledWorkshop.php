<?php

namespace PhpSchool\WorkshopManager\Entity;

final class InstalledWorkshop extends Workshop
{
    /**
     * @var string
     */
    private $version;

    public function __construct(
        string $code,
        string $displayName,
        string $gitHubOwner,
        string $gitHubRepoName,
        string $description,
        string $type,
        string $version
    ) {
        $this->code = $code;
        $this->displayName = $displayName;
        $this->gitHubOwner = $gitHubOwner;
        $this->gitHubRepoName = $gitHubRepoName;
        $this->description = $description;
        $this->type = $type;
        $this->version = $version;
    }

    public static function fromWorkshop(Workshop $workshop, string $version): self
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

    public function getVersion(): string
    {
        return $this->version;
    }
}
