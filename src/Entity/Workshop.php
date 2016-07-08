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
    protected $name;

    /**
     * @var string
     */
    protected $displayName;

    /**
     * @var string
     */
    protected $owner;

    /**
     * @var string
     */
    protected $repo;

    /**
     * @var string
     */
    protected $description;

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
