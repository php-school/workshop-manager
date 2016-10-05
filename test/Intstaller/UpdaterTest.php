<?php

namespace PhpSchool\WorkshopManagerTest\Installer;

use Composer\Repository\InstalledArrayRepository;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Entity\Release;
use PhpSchool\WorkshopManager\Exception\NoUpdateAvailableException;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Installer\Installer;
use PhpSchool\WorkshopManager\Installer\Uninstaller;
use PhpSchool\WorkshopManager\Installer\Updater;
use PhpSchool\WorkshopManager\VersionChecker;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class UpdaterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Installer|PHPUnit_Framework_MockObject_MockObject
     */
    private $installer;

    /**
     * @var Uninstaller|PHPUnit_Framework_MockObject_MockObject
     */
    private $uninstaller;

    /**
     * @var InstalledArrayRepository|PHPUnit_Framework_MockObject_MockObject
     */
    private $installedWorkshopRepository;

    /**
     * @var VersionChecker|PHPUnit_Framework_MockObject_MockObject
     */
    private $versionChecker;

    /**
     * @var Updater
     */
    private $updater;

    public function setup()
    {
        $this->installer = $this->createMock(Installer::class);
        $this->uninstaller = $this->createMock(Uninstaller::class);
        $this->installedWorkshopRepository = $this->createMock(InstalledWorkshopRepository::class);
        $this->versionChecker = $this->createMock(VersionChecker::class);
        $this->updater = new Updater(
            $this->installer,
            $this->uninstaller,
            $this->installedWorkshopRepository,
            $this->versionChecker
        );
    }

    public function testExceptionIsThrownIfNoUpdateAvailable()
    {
        $workshop = new InstalledWorkshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core', 'beginner', '1.0.0');

        $this->installedWorkshopRepository
            ->expects($this->once())
            ->method('getByCode')
            ->willReturn($workshop);

        $this->versionChecker
            ->expects($this->once())
            ->method('getLatestRelease')
            ->willReturn(new Release('1.0.0', 'AAAA'));

        $this->expectException(NoUpdateAvailableException::class);

        $this->updater->updateWorkshop('learn-you-php');
    }

    public function testUpdateUninstallsAndReinstallsNewVersionReturningTheVersionInstalled()
    {
        $workshop = new InstalledWorkshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core', 'beginner', '1.0.0');

        $this->installedWorkshopRepository
            ->expects($this->once())
            ->method('getByCode')
            ->willReturn($workshop);

        $this->versionChecker
            ->expects($this->once())
            ->method('getLatestRelease')
            ->willReturn(new Release('2.0.0', 'AAAA'));

        $this->uninstaller
            ->expects($this->once())
            ->method('uninstallWorkshop')
            ->with('learn-you-php');

        $this->installer
            ->expects($this->once())
            ->method('installWorkshop')
            ->with('learn-you-php');

        $this->updater->updateWorkshop('learn-you-php');
    }
}
