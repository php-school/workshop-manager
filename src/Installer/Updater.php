<?php

namespace PhpSchool\WorkshopManager\Installer;

use PhpSchool\WorkshopManager\Exception\NoUpdateAvailableException;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\VersionChecker;

class Updater
{
    /**
     * @var Installer
     */
    private $installer;

    /**
     * @var Uninstaller
     */
    private $uninstaller;

    /**
     * @var InstalledWorkshopRepository
     */
    private $installedWorkshopRepository;

    /**
     * @var VersionChecker
     */
    private $versionChecker;

    public function __construct(
        Installer $installer,
        Uninstaller $uninstaller,
        InstalledWorkshopRepository $installedWorkshopRepository,
        VersionChecker $versionChecker
    ) {
        $this->installer = $installer;
        $this->uninstaller = $uninstaller;
        $this->installedWorkshopRepository = $installedWorkshopRepository;
        $this->versionChecker = $versionChecker;
    }

    /**
     * @param string $workshopName
     * @return string The updated version.
     */
    public function updateWorkshop(string $workshopName): string
    {
        $workshop = $this->installedWorkshopRepository->getByCode($workshopName);

        $latestRelease = $this->versionChecker->getLatestRelease($workshop);

        if ($latestRelease->getTag() === $workshop->getVersion()) {
            throw new NoUpdateAvailableException();
        }

        $this->uninstaller->uninstallWorkshop($workshopName);
        $this->installer->installWorkshop($workshopName);

        return $latestRelease->getTag();
    }
}
