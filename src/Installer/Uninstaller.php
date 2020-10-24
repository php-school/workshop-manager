<?php

namespace PhpSchool\WorkshopManager\Installer;

use PhpSchool\WorkshopManager\Exception\WorkshopNotInstalledException;
use PhpSchool\WorkshopManager\Filesystem;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use Symfony\Component\Filesystem\Exception\IOException;

class Uninstaller
{
    /**
     * @var InstalledWorkshopRepository
     */
    private $installedWorkshops;

    /**
     * @var Linker
     */
    private $linker;

    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var string
     */
    private $workshopHomeDirectory;

    public function __construct(
        InstalledWorkshopRepository $installedWorkshops,
        Linker $linker,
        Filesystem $filesystem,
        string $workshopHomeDirectory
    ) {
        $this->filesystem = $filesystem;
        $this->installedWorkshops = $installedWorkshops;
        $this->workshopHomeDirectory = $workshopHomeDirectory;
        $this->linker = $linker;
    }

    /**
     * @param string $workshop
     */
    public function uninstallWorkshop(string $workshop): void
    {
        if (!$this->installedWorkshops->hasWorkshop($workshop)) {
            throw new WorkshopNotInstalledException();
        }

        $workshop = $this->installedWorkshops->getByCode($workshop);

        try {
            $this->filesystem->remove(sprintf('%s/workshops/%s', $this->workshopHomeDirectory, $workshop->getCode()));
        } catch (IOException $e) {
            throw $e;
        }

        $this->installedWorkshops->remove($workshop);
        $this->installedWorkshops->save();

        $this->linker->unlink($workshop);
    }
}
