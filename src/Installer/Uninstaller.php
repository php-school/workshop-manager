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

    /**
     * @param InstalledWorkshopRepository $installedWorkshops
     * @param Linker $linker
     * @param Filesystem $filesystem
     * @param $workshopHomeDirectory
     */
    public function __construct(
        InstalledWorkshopRepository $installedWorkshops,
        Linker $linker,
        Filesystem $filesystem,
        $workshopHomeDirectory
    ) {
        $this->filesystem         = $filesystem;
        $this->installedWorkshops = $installedWorkshops;
        $this->workshopHomeDirectory = $workshopHomeDirectory;
        $this->linker = $linker;
    }

    /**
     * @param string $workshop
     *
     * @throws WorkshopNotInstalledException
     * @throws \RuntimeException When filesystem delete fails
     */
    public function uninstallWorkshop($workshop)
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
