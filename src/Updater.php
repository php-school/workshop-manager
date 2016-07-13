<?php

namespace PhpSchool\WorkshopManager;

use PhpSchool\WorkshopManager\Entity\Release;
use PhpSchool\WorkshopManager\Exception\NoUpdateAvailableException;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
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

    /**
     * @param Installer $installer
     * @param Uninstaller $uninstaller
     * @param InstalledWorkshopRepository $installedWorkshopRepository
     * @param VersionChecker $versionChecker
     */
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
     * @param bool $force
     * @return string The updated version.
     */
    public function updateWorkshop($workshopName, $force = false)
    {
        $workshop = $this->installedWorkshopRepository->getByName($workshopName);

        if (!$this->versionChecker->hasUpdate($workshop)) {
            throw new NoUpdateAvailableException;
        }

        $version = $this->versionChecker->checkForUpdates($workshop, function (Release $release, $updated) {
            return $updated ? $release->getTag() : null;
        });

        if (!$version) {
            throw new NoUpdateAvailableException;
        }

        $this->uninstaller->uninstallWorkshop($workshopName, $force);
        $this->installer->installWorkshop($workshopName, $force);

        return $version;
    }
}
