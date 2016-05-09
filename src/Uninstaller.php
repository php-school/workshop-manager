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
     * @var Linker
     */
    private $linker;

    /**
     * @param Filesystem $filesystem
     * @param ManagerState $state
     * @param Linker $linker
     */
    public function __construct(Filesystem $filesystem, ManagerState $state, Linker $linker)
    {
        $this->filesystem = $filesystem;
        $this->state      = $state;
        $this->linker     = $linker;
    }

    /**
     * @param Workshop $workshop
     */
    public function uninstallWorkshop(Workshop $workshop)
    {
        if (!$this->state->isWorkshopInstalled($workshop)) {
            throw new WorkshopNotInstalledException;
        }

        // TODO: Add error handling.
        $this->linker->unlink($workshop);

        if (!$this->filesystem->deleteDir(sprintf('workshops/%s', $workshop->getName()))) {
            throw new \RuntimeException;
        }
    }
}
