<?php

namespace PhpSchool\WorkshopManagerTest\Installer;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotInstalledException;
use PhpSchool\WorkshopManager\Filesystem;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Installer\Uninstaller;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class UninstallerTest extends PHPUnit_Framework_TestCase
{
    private $localJsonFile;
    private $installedWorkshopRepo;
    private $linker;
    private $filesystem;
    private $workshopHomeDir;

    /**
     * @var Uninstaller
     */
    private $uninstaller;

    public function setup()
    {
        $this->localJsonFile = $this->createMock(JsonFile::class);
        $this->localJsonFile
            ->expects($this->once())
            ->method('read')
            ->willReturn(['workshops' => []]);

        $this->installedWorkshopRepo = new InstalledWorkshopRepository($this->localJsonFile);
        $this->linker = $this->createMock(Linker::class);
        $this->filesystem = new Filesystem;
        $this->workshopHomeDir = sprintf('%s/%s', realpath(sys_get_temp_dir()), $this->getName());
        @mkdir($this->workshopHomeDir);
        $this->uninstaller = new Uninstaller(
            $this->installedWorkshopRepo,
            $this->linker,
            $this->filesystem,
            $this->workshopHomeDir
        );
    }

    public function tearDown()
    {
        @chmod(sprintf('%s/workshops/learn-you-php', $this->workshopHomeDir), 0775);
        $this->filesystem->remove($this->workshopHomeDir);
    }

    public function testExceptionIsThrownIfWorkshopIsNotInstalled()
    {
        $this->expectException(WorkshopNotInstalledException::class);
        $this->uninstaller->uninstallWorkshop('learn-you-php');
    }

    public function testExceptionIsThrownIfFilesCannotBeRemoved()
    {
        $dir = sprintf('%s/workshops/learn-you-php', $this->workshopHomeDir);
        mkdir($dir, 0775, true);

        touch(sprintf('%s/file1.php', $dir));
        chmod($dir, 0555);

        $this->expectException(IOException::class);
        $this->expectExceptionMessageRegExp('/Failed to remove file.*/');

        $this->installedWorkshopRepo->add(
            new InstalledWorkshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core', 'beginner', '1.0.0')
        );

        $this->uninstaller->uninstallWorkshop('learn-you-php');
    }

    public function testRemove()
    {
        $dir = sprintf('%s/workshops/learn-you-php', $this->workshopHomeDir);
        mkdir($dir, 0775, true);

        $this->installedWorkshopRepo->add(
            new InstalledWorkshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core', 'beginner', '1.0.0')
        );

        $this->localJsonFile
            ->expects($this->once())
            ->method('write');

        $this->linker
            ->expects($this->once())
            ->method('unlink')
            ->with($this->isInstanceOf(InstalledWorkshop::class));

        $this->uninstaller->uninstallWorkshop('learn-you-php');

        $this->assertTrue($this->installedWorkshopRepo->isEmpty());
        $this->assertFileNotExists($dir);
    }
}
