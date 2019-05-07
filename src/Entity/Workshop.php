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

    /**
     * @param string $code
     * @param string $displayName
     * @param string $gitHubOwner
     * @param string $gitHubRepoName
     * @param string $description
     * @param string $type
     */
    public function __construct($code, $displayName, $gitHubOwner, $gitHubRepoName, $description, $type)
    {
        $this->code             = $code;
        $this->displayName      = $displayName;
        $this->gitHubOwner      = $gitHubOwner;
        $this->gitHubRepoName   = $gitHubRepoName;
        $this->description      = $description;
        $this->type             = $type;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }


    /**
     * @return string
     */
    public function getGitHubOwner()
    {
        return $this->gitHubOwner;
    }

    /**
     * @return string
     */
    public function getGitHubRepoName()
    {
        return $this->gitHubRepoName;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
