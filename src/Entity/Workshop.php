<?php

namespace PhpSchool\WorkshopManager\Entity;

class Workshop
{
    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $displayName;

    /**
     * @var string
     */
    protected $gitHubOwner;

    /**
     * @var string
     */
    protected $gitHubRepoName;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $type;

    public function __construct(
        string $code,
        string $displayName,
        string $gitHubOwner,
        string $gitHubRepoName,
        string $description,
        string $type
    ) {
        $this->code = $code;
        $this->displayName = $displayName;
        $this->gitHubOwner = $gitHubOwner;
        $this->gitHubRepoName = $gitHubRepoName;
        $this->description = $description;
        $this->type = $type;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getGitHubOwner(): string
    {
        return $this->gitHubOwner;
    }

    public function getGitHubRepoName(): string
    {
        return $this->gitHubRepoName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
