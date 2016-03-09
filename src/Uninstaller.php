<?php

namespace PhpSchool\WorkshopManager;

use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Exception\WorkshopNotInstalledException;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;

/**
 * Class Uninstaller
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
final class Uninstaller
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
     * @var ManagerState
     */
    private $state;

    /**
     * @param Filesystem $filesystem
     * @param WorkshopRepository $repository
     * @param ManagerState $state
     */
    public function __construct(Filesystem $filesystem, WorkshopRepository $repository, ManagerState $state)
    {
        $this->filesystem = $filesystem;
        $this->repository = $repository;
        $this->state      = $state;
    }

    /**
     * @param $name
     * @throws WorkshopNotInstalledException
     * @throws \RuntimeException On unexpected failure
     */
    public function uninstallWorkshop($name)
    {
        if (!$this->state->isWorkshopInstalled($name)) {
            throw new WorkshopNotInstalledException;
        }

        if (!$this->filesystem->deleteDir(sprintf('workshops/%s', $name))) {
            throw new \RuntimeException;
        }
    }
}
