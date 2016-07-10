<?php

namespace PhpSchool\WorkshopManagerTest;

use Composer\Factory;
use Composer\IO\NullIO;
use Github\Api\GitData;
use Github\Api\GitData\Tags;
use Github\Api\Repo;
use Github\Api\Repository\Contents;
use Github\Client;
use Github\Exception\RuntimeException;
use PhpSchool\WorkshopManager\ComposerInstallerFactory;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use PhpSchool\WorkshopManager\Exception\FailedToMoveWorkshopException;
use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
use PhpSchool\WorkshopManager\Filesystem;
use PhpSchool\WorkshopManager\Installer;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PHPUnit_Framework_TestCase;

/**
 * Class InstallerTest
 * @package PhpSchool\WorkshopManagerTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class InstallerTest extends PHPUnit_Framework_TestCase
{
    private $filesystem;
    private $composerFactory;
    private $installedWorkshopRepo;
    private $workshopHomeDir;
    private $ghClient;
    private $installer;

    public function setup()
    {
        $this->filesystem = new Filesystem;
        $this->composerFactory = new ComposerInstallerFactory(new Factory, new NullIO);
        $this->installedWorkshopRepo = $this->createMock(InstalledWorkshopRepository::class);
        $this->workshopHomeDir = sprintf('%s/%s', realpath(sys_get_temp_dir()), $this->getName());
        @mkdir($this->workshopHomeDir);
        $this->ghClient = $this->createMock(Client::class);
        $this->installer = new Installer(
            $this->installedWorkshopRepo,
            $this->filesystem,
            $this->workshopHomeDir,
            $this->composerFactory,
            $this->ghClient
        );
    }

    public function tearDown()
    {
        @chmod(sprintf('%s/.temp', $this->workshopHomeDir), 0775);
        @chmod(sprintf('%s/.temp/learn-you-php.zip', $this->workshopHomeDir), 0775);
        $this->filesystem->remove($this->workshopHomeDir);
    }


    public function testExceptionIsThrownIfWorkshopWithSameNameAlreadyExists()
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop');

        $this->installedWorkshopRepo
            ->expects($this->once())
            ->method('hasWorkshop')
            ->with('learn-you-php')
            ->willReturn(true);

        $this->expectException(WorkshopAlreadyInstalledException::class);
        $this->installer->installWorkshop($workshop);
    }

    public function testExceptionIsThrownIfTagsCannotBeLoaded()
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop');

        $gitData = $this->createMock(GitData::class);
        $tags = $this->createMock(Tags::class);

        $this->ghClient
            ->expects($this->any())
            ->method('api')
            ->with('git')
            ->willReturn($gitData);

        $gitData
            ->expects($this->any())
            ->method('tags')
            ->willReturn($tags);

        $tags
            ->expects($this->once())
            ->method('all')
            ->with($workshop->getOwner(), $workshop->getRepo())
            ->willThrowException(new RuntimeException('Tag Failure'));

        $this->expectException(DownloadFailureException::class);
        $this->expectExceptionMessage('Tag Failure');

        $this->installer->installWorkshop($workshop);
    }

    public function testExceptionIsThrowIfWorkshopTempDownloadFileExistsAndCannotBeRemoved()
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop');
        $this->configureTags($workshop);

        $path = sprintf('%s/.temp/learn-you-php.zip', $this->workshopHomeDir);
        @mkdir(dirname($path));
        touch($path);
        chmod($path, 0444);
        chmod(dirname($path), 0555);

        $this->expectException(DownloadFailureException::class);
        $this->expectExceptionMessageRegExp('/Failed to remove file.*/');
        $this->installer->installWorkshop($workshop);
        unlink($path);
        rmdir(dirname($path));
    }

    public function testExceptionIsThrownIfWorkshopCannotBeDownloaded()
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop');

        $this->configureTags($workshop);

        $repo = $this->createMock(Repo::class);
        $contents = $this->createMock(Contents::class);

        $this->ghClient
            ->expects($this->at(1))
            ->method('api')
            ->with('repo')
            ->willReturn($repo);

        $repo
            ->expects($this->any())
            ->method('contents')
            ->willReturn($contents);

        $contents
            ->expects($this->once())
            ->method('archive')
            ->with($workshop->getOwner(), $workshop->getRepo(), 'zipball', '0123456789')
            ->willThrowException(new RuntimeException('Download failure'));

        $this->expectException(DownloadFailureException::class);
        $this->expectExceptionMessage('Download failure');

        $this->installer->installWorkshop($workshop);

    }

    public function testExceptionIsThrownIfWorkshopCannotBeSaved()
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop');
        $this->configureTags($workshop);
        $this->configureDownload($workshop);

        $path = sprintf('%s/.temp/', $this->workshopHomeDir);
        @mkdir($path);
        chmod($path, 0555);

        $this->expectException(DownloadFailureException::class);
        $this->expectExceptionMessageRegExp('/^Unable to write to the.*/');

        $this->installer->installWorkshop($workshop);
    }

    public function testExceptionIsThrownIfCannotMoveWorkshopToInstallDir()
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop');
        $this->configureTags($workshop);
        $this->configureDownload($workshop);

        $path = sprintf('%s/workshops/', $this->workshopHomeDir);
        @mkdir($path);
        chmod($path, 0555);

        $this->expectException(FailedToMoveWorkshopException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Failed to move workshop files from "%s/.temp/learnyouphp/" to "%s/workshops/learn-you-php"',
                $this->workshopHomeDir,
                $this->workshopHomeDir
            )
        );

        $this->installer->installWorkshop($workshop);
    }

    public function testExceptionIsThrownIfCannotRunComposerInstall()
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop');
        $this->configureTags($workshop);
        $this->configureDownload($workshop, false);

        $path = sprintf('%s/workshops/', $this->workshopHomeDir);
        @mkdir($path);

        $this->expectException(ComposerFailureException::class);
        $this->expectExceptionMessageRegExp('/^Composer could not find the config.*/');

        $this->installer->installWorkshop($workshop);
    }

    public function testSuccessfulInstall()
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop');
        $this->configureTags($workshop);
        $this->configureDownload($workshop);

        $path = sprintf('%s/workshops/', $this->workshopHomeDir);
        @mkdir($path);

        $this->assertSame('1.0.0', $this->installer->installWorkshop($workshop));
    }

    public function testWorkshopDirIsCreatedIfNotExists()
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop');
        $this->configureTags($workshop);
        $this->configureDownload($workshop);

        $this->assertSame('1.0.0', $this->installer->installWorkshop($workshop));
    }

    public function testWorkshopNameFolderIsRemovedIfExists()
    {
        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop');
        $this->configureTags($workshop);
        $this->configureDownload($workshop);
        mkdir(sprintf('%s/workshops/learn-you-php', $this->workshopHomeDir), 0775, true);

        $this->assertSame('1.0.0', $this->installer->installWorkshop($workshop));
    }

    private function configureTags(Workshop $workshop)
    {
        $gitData = $this->createMock(GitData::class);
        $tags = $this->createMock(Tags::class);

        $this->ghClient
            ->expects($this->at(0))
            ->method('api')
            ->with('git')
            ->willReturn($gitData);

        $gitData
            ->expects($this->any())
            ->method('tags')
            ->willReturn($tags);

        $tags
            ->expects($this->once())
            ->method('all')
            ->with($workshop->getOwner(), $workshop->getRepo())
            ->willReturn([
                [
                    'ref' => 'refs/tags/1.0.0',
                    'object' => ['sha' => '0123456789']
                 ]
            ]);
    }

    private function configureDownload(Workshop $workshop, $correctComposerJson = true)
    {
        $repo = $this->createMock(Repo::class);
        $contents = $this->createMock(Contents::class);

        $this->ghClient
            ->expects($this->at(1))
            ->method('api')
            ->with('repo')
            ->willReturn($repo);

        $repo
            ->expects($this->any())
            ->method('contents')
            ->willReturn($contents);

        $zipArchive = new \ZipArchive;
        $zipArchive->open(sprintf('%s/temp.zip', $this->workshopHomeDir), \ZipArchive::CREATE);
        $zipArchive->addEmptyDir('learnyouphp');
        $zipArchive->addFromString('learnyouphp/file1.txt', 'data');

        if ($correctComposerJson) {
            $zipArchive->addFromString('learnyouphp/composer.json', '{"name" : "learnyouphp"}');
        }

        $zipArchive->close();

        $contents
            ->expects($this->once())
            ->method('archive')
            ->with($workshop->getOwner(), $workshop->getRepo(), 'zipball', '0123456789')
            ->willReturn(file_get_contents(sprintf('%s/temp.zip', $this->workshopHomeDir)));

        unlink(sprintf('%s/temp.zip', $this->workshopHomeDir));
    }
}
