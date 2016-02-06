<?php

namespace PhpSchool\WorkshopManager\Entity;

/**
 * Class Workshop
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class Workshop
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $displayName;

    /**
     * @var string
     */
    private $owner;

    /**
     * @var string
     */
    private $repo;

    /**
     * @var string
     */
    private $description;

    /**
     * @param string $name
     * @param string $displayName
     * @param string $owner
     * @param string $repo
     * @param string $description
     */
    public function __construct($name, $displayName, $owner, $repo, $description)
    {
        $this->name        = $name;
        $this->displayName = $displayName;
        $this->owner       = $owner;
        $this->repo        = $repo;
        $this->description = $description;
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return string
     */
    public function getRepo()
    {
        return $this->repo;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
