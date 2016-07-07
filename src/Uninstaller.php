<?php

namespace PhpSchool\WorkshopManager;

use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotInstalledException;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use Symfony\Component\Filesystem\Exception\IOException;

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
     * @var string
     */
    private $workshopHomeDirectory;

    /**
     * @param InstalledWorkshopRepository $installedWorkshops
     * @param Filesystem $filesystem
     * @param string $workshopHomeDirectory
     */
    public function __construct(
        InstalledWorkshopRepository $installedWorkshops,
        Filesystem $filesystem,
        $workshopHomeDirectory
    ) {
        $this->filesystem         = $filesystem;
        $this->installedWorkshops = $installedWorkshops;
        $this->workshopHomeDirectory = $workshopHomeDirectory;
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
        if (!$this->installedWorkshops->hasWorkshop($workshop->getName())) {
            throw new WorkshopNotInstalledException;
        }

        try {
            $this->filesystem->remove(sprintf('%s/workshops/%s', $this->workshopHomeDirectory, $workshop->getName()));
        } catch (IOException $e) {
            throw $e;
        }
    }
}
