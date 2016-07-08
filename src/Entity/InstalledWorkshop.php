<?php

namespace PhpSchool\WorkshopManager\Entity;

/**
 * Class InstalledWorkshop
 * @package PhpSchool\WorkshopManager\Entity
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class InstalledWorkshop extends Workshop
{
    /**
     * @var string
     */
    protected $version;

    /**
     * @param string $name
     * @param string $displayName
     * @param string $owner
     * @param string $repo
     * @param string $description
     * @param string $version
     */
    public function __construct($name, $displayName, $owner, $repo, $description, $version)
    {
        $this->name = $name;
        $this->displayName = $displayName;
        $this->owner = $owner;
        $this->repo = $repo;
        $this->description = $description;
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
            $workshop->getName(),
            $workshop->getDisplayName(),
            $workshop->getOwner(),
            $workshop->getRepo(),
            $workshop->getDescription(),
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
