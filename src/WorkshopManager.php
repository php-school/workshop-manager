<?php

namespace PhpSchool\WorkshopManager;

use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;

/**
 * Class WorkshopManager
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class WorkshopManager
{
    /**
     * @var WorkshopInstaller
     */
    private $installer;

    /**
     * @var WorkshopRepository
     */
    private $repository;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param WorkshopInstaller $installer
     * @param Filesystem $filesystem
     * @param WorkshopRepository $repository
     */
    public function __construct(WorkshopInstaller $installer, WorkshopRepository $repository, Filesystem $filesystem)
    {
        $this->installer  = $installer;
        $this->repository = $repository;
        $this->filesystem = $filesystem;
    }

    /**
     * @return Workshop[]
     */
    public function getInstalledWorkshops()
    {
        return array_filter(array_map(function ($listing) {
            try {
                return $this->repository->getByName($listing['basename']);
            } catch (WorkshopNotFoundException $e) {
                return false;
            }
        }, $this->filesystem->listContents('workshops')));
    }


    public function installWorkshop($name)
    {
        $workshop = $this->repository->getByName($name);

        $this->installer->install($workshop);
    }

    public function uninstallWorkshop($name)
    {

    }
}
