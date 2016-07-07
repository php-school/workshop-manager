<?php

namespace PhpSchool\WorkshopManager\Repository;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\RequiresNetworkAccessException;

/**
 * Class InstalledWorkshopRepository
 * @package PhpSchool\WorkshopManager\Repository
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class RemoteWorkshopRepository implements WorkshopRepository
{

    /**
     * flag to indicate remote repo has been loaded
     *
     * @var bool
     */
    private $initialised = false;

    /**
     * @var InstalledWorkshopRepository|null
     */
    private $wrapped = null;

    /**
     * @var JsonFile
     */
    private $remoteJsonFile;

    /**
     * @param JsonFile $remoteJsonFile
     */
    public function __construct(JsonFile $remoteJsonFile)
    {
        $this->remoteJsonFile = $remoteJsonFile;
    }

    /**
     * @param Workshop $workshop
     */
    public function addWorkshop(Workshop $workshop)
    {
        return $this->wrapped->addWorkshop($workshop);
    }

    /**
     * @return array
     */
    public function getAll()
    {
        $this->init();
        return $this->wrapped->getAll();
    }

    /**
     * @param string $name
     *
     * @return Workshop
     * @throws WorkshopNotFoundException
     */
    public function getByName($name)
    {
        $this->init();
        return $this->wrapped->getByName($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasWorkshop($name)
    {
        $this->init();
        return $this->wrapped->hasWorkshop($name);
    }

    /**
     * @param string $searchName
     *
     * @return Workshop[]
     */
    public function find($searchName)
    {
        $this->init();
        return $this->wrapped->find($searchName);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        $this->init();
        return $this->wrapped->isEmpty();
    }

    /**
     * Load the remote data
     *
     * @throws RequiresNetworkAccessException
     */
    private function init()
    {
        if ($this->initialised) {
            return;
        }

        if (!checkdnsrr(parse_url($this->remoteJsonFile->getPath(), PHP_URL_HOST), 'A')) {
            throw new RequiresNetworkAccessException;
        }

        $this->wrapped = new InstalledWorkshopRepository($this->remoteJsonFile);
        $this->initialised = true;
    }
}
