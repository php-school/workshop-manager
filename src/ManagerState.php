<?php

namespace PhpSchool\WorkshopManager;

use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;

/**
 * Class ManagerState
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
final class ManagerState
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var WorkshopRepository
     */
    private $repository;

    /**
     * @param Filesystem $filesystem
     * @param WorkshopRepository $repository
     */
    public function __construct(Filesystem $filesystem, WorkshopRepository $repository)
    {
        $this->filesystem = $filesystem;
        $this->repository = $repository;
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

    /**
     * @param Workshop $workshop
     * @return bool
     */
    public function isWorkshopInstalled(Workshop $workshop)
    {
        foreach ($this->getInstalledWorkshops() as $installedWorkshop) {
            if ($workshop->getName() === $installedWorkshop->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function clearTemp()
    {
        return $this->filesystem->deleteDir('.temp');
    }
}
