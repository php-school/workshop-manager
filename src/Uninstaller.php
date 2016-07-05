<?php

namespace PhpSchool\WorkshopManager;

use League\Flysystem\Filesystem;
use League\Flysystem\RootViolationException;
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
     * @var WorkshopRepository
     */
    private $installedWorkshops;

    /**
     * @param Filesystem $filesystem
     * @param WorkshopRepository $installedWorkshops
     */
    public function __construct(Filesystem $filesystem, WorkshopRepository $installedWorkshops)
    {
        $this->filesystem         = $filesystem;
        $this->installedWorkshops = $installedWorkshops;
    }

    /**
     * @param Workshop $workshop
     *
     * @throws WorkshopNotInstalledException
     * @throws \RuntimeException When filesystem delete fails
     * @throws RootViolationException In non existant circumstances :)
     */
    public function uninstallWorkshop(Workshop $workshop)
    {
        if ($this->installedWorkshops->hasWorkshop($workshop->getName())) {
            throw new WorkshopNotInstalledException;
        }

        if (!$this->filesystem->deleteDir(sprintf('workshops/%s', $workshop->getName()))) {
            throw new \RuntimeException;
        }
    }
}
