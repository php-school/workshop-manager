<?php

declare(strict_types=1);

namespace PhpSchool\WorkshopManager\Entity;

class Branch
{
    /**
     * @var string
     */
    private static $gitHubRepoUrlRegex = '/^(https?:\/\/)?(www.)?github.com\/([A-Za-z\d-]+)\/([A-Za-z\d\.-]+)\/?$/';

    /**
     * @var string
     */
    private $branch;

    /**
     * @var ?string
     */
    private $gitHubRepository;

    /**
     * @var ?string
     */
    private $gitHubOwner;

    /**
     * @var ?string
     */
    private $gitHubRepoName;

    public function __construct(string $branch, string $gitHubRepository = null)
    {
        $this->branch = $branch;
        $this->gitHubRepository = $gitHubRepository;

        if ($this->gitHubRepository !== null) {
            preg_match(static::$gitHubRepoUrlRegex, $this->gitHubRepository, $matches);
            $this->gitHubOwner = $matches[3];
            $this->gitHubRepoName = $matches[4];
        }
    }

    public function getBranch(): string
    {
        return $this->branch;
    }

    public function isDifferentRepository(): bool
    {
        return $this->gitHubRepository !== null;
    }

    public function getGitHubOwner(): ?string
    {
        return $this->gitHubOwner;
    }

    public function getGitHubRepoName(): ?string
    {
        return $this->gitHubRepoName;
    }

    public function __toString(): string
    {
        return $this->isDifferentRepository()
            ? $this->gitHubRepository . ':' . $this->branch
            : $this->branch;
    }
}
