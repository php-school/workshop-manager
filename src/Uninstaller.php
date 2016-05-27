<?php

namespace PhpSchool\WorkshopManager;

use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Entity\Workshop;
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
     * @var ManagerState
     */
    private $state;

    /**
     * @param Filesystem $filesystem
     * @param ManagerState $state
     */
    public function __construct(Filesystem $filesystem, ManagerState $state)
    {
        $this->filesystem = $filesystem;
        $this->state      = $state;
    }

    /**
     * @param Workshop $workshop
     *
     * @throws WorkshopNotInstalledException
     * @throws \RuntimeException When filesystem delete fails
     */
    public function uninstallWorkshop(Workshop $workshop)
    {
        if (!$this->state->isWorkshopInstalled($workshop)) {
            throw new WorkshopNotInstalledException;
        }

        if (!$this->filesystem->deleteDir(sprintf('workshops/%s', $workshop->getName()))) {
            throw new \RuntimeException;
        }
    }
}
