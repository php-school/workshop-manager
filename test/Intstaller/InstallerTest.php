<?php

namespace PhpSchool\WorkshopManagerTest\Installer;

use Composer\Json\JsonFile;
use Composer\Package\Archiver\ZipArchiver;
use Github\Api\GitData;
use Github\Api\GitData\Tags;
use Github\Api\Repo;
use Github\Api\Repository\Contents;
use Github\Client;
use Github\Exception\RuntimeException;
use PhpSchool\WorkshopManager\ComposerInstaller;
use PhpSchool\WorkshopManager\ComposerInstallerFactory;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use PhpSchool\WorkshopManager\Exception\FailedToMoveWorkshopException;
use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Filesystem;
use PhpSchool\WorkshopManager\Installer\Installer;
use PhpSchool\WorkshopManager\InstallResult;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\RemoteWorkshopRepository;
use PhpSchool\WorkshopManager\VersionChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class InstallerTest extends TestCase
{
    private $localJsonFile;
    private $installedWorkshopRepo;
    private $remoteWorkshopRepo;
    private $linker;
    private $filesystem;
    private $workshopHomeDir;
    private $composerInstaller;
    private $versionChecker;
    private $ghClient;
    private $installer;

    public function setup(): void
    {
        $this->localJsonFile = $this->createMock(JsonFile::class);
        $this->localJsonFile
            ->expects($this->once())
            ->method('read')
            ->willReturn(['workshops' => []]);

        $this->installedWorkshopRepo = new InstalledWorkshopRepository($this->localJsonFile);
        $this->remoteWorkshopRepo = $this->createMock(RemoteWorkshopRepository::class);
        $this->linker = $this->createMock(Linker::class);
        $this->filesystem = new Filesystem;
        $this->workshopHomeDir = sprintf('%s/%s', realpath(sys_get_temp_dir()), $this->getName());
        $this->composerInstaller = $this->createMock(ComposerInstaller::class);
        @mkdir($this->workshopHomeDir);
        $this->ghClient = $this->createMock(Client::class);
        $this->versionChecker = new VersionChecker($this->ghClient);
        $this->installer = new Installer(
            $this->installedWorkshopRepo,
            $this->remoteWorkshopRepo,
            $this->linker,
            $this->filesystem,
            $this->workshopHomeDir,
            $this->composerInstaller,
            $this->ghClient,
            $this->versionChecker,
            '/dev/null/%s/%s'
        );
    }

    public function tearDown(): void
    {
        @chmod(sprintf('%s/.temp', $this->workshopHomeDir), 0775);
        @chmod(sprintf('%s/.temp/learn-you-php.zip', $this->workshopHomeDir), 0775);
        $this->filesystem->remove($this->workshopHomeDir);
    }

    public function testExceptionIsThrownIfWorkshopWithSameNameAlreadyExists(): void
    {
        $this->installedWorkshopRepo->add(
            new InstalledWorkshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core', '1.0.0')
        );

        $this->expectException(WorkshopAlreadyInstalledException::class);
        $this->installer->installWorkshop('learn-you-php');
    }

    public function testExceptionIsThrowIfWorkshopDoesNotExistInRegistry(): void
    {
        $this->remoteWorkshopRepo
            ->expects($this->once())
            ->method('hasWorkshop')
            ->with('learn-you-php')
            ->willReturn(false);

        $this->expectException(WorkshopNotFoundException::class);
        $this->installer->installWorkshop('learn-you-php');
    }

    public function testExceptionIsWrappedIfGetLatestReleaseThrowsException(): void
    {
        $workshop = $this->configureRemoteRepository();

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
            ->with($workshop->getGitHubOwner(), $workshop->getGitHubRepoName())
            ->willThrowException(new RuntimeException('Tag Failure'));

        $this->expectException(DownloadFailureException::class);
        $this->expectExceptionMessage('Cannot communicate with GitHub - check your internet connection');

        $this->installer->installWorkshop($workshop->getCode());
    }

    public function testExceptionIsThrowIfWorkshopTempDownloadFileExistsAndCannotBeRemoved(): void
    {
        $workshop = $this->configureRemoteRepository();
        $this->configureGitHubApi($workshop, false);

        $path = sprintf('%s/.temp/learn-you-php.zip', $this->workshopHomeDir);
        @mkdir(dirname($path));
        touch($path);
        chmod($path, 0444);
        chmod(dirname($path), 0555);

        $this->expectException(DownloadFailureException::class);
        $this->expectExceptionMessageMatches('/Failed to remove file.*/');
        $this->installer->installWorkshop($workshop->getCode());
        unlink($path);
        rmdir(dirname($path));
    }

    public function testExceptionIsThrownIfWorkshopCannotBeDownloaded(): void
    {
        $workshop = $this->configureRemoteRepository();

        $gitData = $this->createMock(GitData::class);
        $tags = $this->createMock(Tags::class);

        $contents = $this->createMock(Contents::class);
        $contents
            ->expects($this->once())
            ->method('archive')
            ->with($workshop->getGitHubOwner(), $workshop->getGitHubRepoName(), 'zipball', '0123456789')
            ->willThrowException(new RuntimeException('Download failure'));

        $repo = $this->createMock(Repo::class);
        $repo->method('contents')->willReturn($contents);

        $this->ghClient
            ->expects($this->exactly(2))
            ->method('api')
            ->withConsecutive(['git'], ['repo'])
            ->willReturnOnConsecutiveCalls($gitData, $repo);

        $gitData->method('tags')->willReturn($tags);

        $tags
            ->expects($this->once())
            ->method('all')
            ->with($workshop->getGitHubOwner(), $workshop->getGitHubRepoName())
            ->willReturn([
                [
                    'ref' => 'refs/tags/1.0.0',
                    'object' => ['sha' => '0123456789']
                ]
            ]);

        $this->expectException(DownloadFailureException::class);
        $this->expectExceptionMessage('Download failure');

        $this->installer->installWorkshop($workshop->getCode());
    }

    public function testExceptionIsThrownIfWorkshopCannotBeSaved(): void
    {
        $workshop = $this->configureRemoteRepository();
        $this->configureGitHubApi($workshop, true);

        $path = sprintf('%s/.temp/', $this->workshopHomeDir);
        @mkdir($path);
        chmod($path, 0555);

        $this->expectException(DownloadFailureException::class);
        $this->expectExceptionMessageMatches('/^Unable to write to the.*/');

        $this->installer->installWorkshop($workshop->getCode());
    }

    public function testExceptionIsThrownIfCannotMoveWorkshopToInstallDir(): void
    {
        $workshop = $this->configureRemoteRepository();
        $this->configureGitHubApi($workshop, true);

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

        $this->installer->installWorkshop($workshop->getCode());
    }

    public function testExceptionIsThrownIfCannotRunComposerInstall(): void
    {
        $path = sprintf('%s/workshops/', $this->workshopHomeDir);
        @mkdir($path);

        $workshop = $this->configureRemoteRepository();
        $this->configureGitHubApi($workshop, true);

        $this->composerInstaller
            ->expects($this->once())
            ->method('install')
            ->with(sprintf('%slearn-you-php', $path))
            ->will($this->throwException(new \InvalidArgumentException('composer.json not found')));

        $this->expectException(ComposerFailureException::class);
        $this->expectExceptionMessage('composer.json not found');

        $this->installer->installWorkshop($workshop->getCode());
    }

    public function testExceptionIsThrownIfCannotRunComposerInstallBecauseMissingExtensions(): void
    {
        $path = sprintf('%s/workshops/', $this->workshopHomeDir);
        @mkdir($path);

        $workshop = $this->configureRemoteRepository();
        $this->configureGitHubApi($workshop, true);

        $this->composerInstaller
            ->expects($this->once())
            ->method('install')
            ->with(sprintf('%slearn-you-php', $path))
            ->willReturn(new InstallResult(1, "the requested PHP extension mbstring is missing from your system\n"));

        $message  = 'This workshop requires some extra PHP extensions. Please install them';
        $message .= ' and try again. Required extensions are mbstring.';

        $this->expectException(ComposerFailureException::class);
        $this->expectExceptionMessage($message);

        $this->installer->installWorkshop($workshop->getCode());
    }

    public function testSuccessfulInstall(): void
    {
        $workshop = $this->configureRemoteRepository();
        $this->configureGitHubApi($workshop, true);

        $path = sprintf('%s/workshops/', $this->workshopHomeDir);
        @mkdir($path);

        $this->composerInstaller
            ->expects($this->once())
            ->method('install')
            ->with(sprintf('%slearn-you-php', $path))
            ->willReturn(new InstallResult(0, ''));

        $this->localJsonFile
            ->expects($this->once())
            ->method('write');

        $this->linker
            ->expects($this->once())
            ->method('link')
            ->with($this->isInstanceOf(InstalledWorkshop::class));

        $this->installer->installWorkshop($workshop->getCode());

        $this->assertTrue($this->installedWorkshopRepo->hasWorkshop('learn-you-php'));
        $this->assertFileExists(sprintf('%s/workshops/learn-you-php', $this->workshopHomeDir));
    }

    public function testWorkshopDirIsCreatedIfNotExists(): void
    {
        $workshop = $this->configureRemoteRepository();
        $this->configureGitHubApi($workshop, true);

        $this->composerInstaller
            ->expects($this->once())
            ->method('install')
            ->with(sprintf('%s/workshops/learn-you-php', $this->workshopHomeDir))
            ->willReturn(new InstallResult(0, ''));

        $this->localJsonFile
            ->expects($this->once())
            ->method('write');

        $this->linker
            ->expects($this->once())
            ->method('link')
            ->with($this->isInstanceOf(InstalledWorkshop::class));

        $this->installer->installWorkshop($workshop->getCode());

        $this->assertTrue($this->installedWorkshopRepo->hasWorkshop('learn-you-php'));
        $this->assertFileExists(sprintf('%s/workshops/learn-you-php', $this->workshopHomeDir));
    }

    public function testWorkshopNameFolderIsRemovedIfExists(): void
    {
        $workshop = $this->configureRemoteRepository();
        $this->configureGitHubApi($workshop, true);

        mkdir(sprintf('%s/workshops/learn-you-php', $this->workshopHomeDir), 0775, true);

        $this->composerInstaller
            ->expects($this->once())
            ->method('install')
            ->with(sprintf('%s/workshops/learn-you-php', $this->workshopHomeDir))
            ->willReturn(new InstallResult(0, ''));

        $this->localJsonFile
            ->expects($this->once())
            ->method('write');

        $this->linker
            ->expects($this->once())
            ->method('link')
            ->with($this->isInstanceOf(InstalledWorkshop::class));

        $this->installer->installWorkshop($workshop->getCode());

        $this->assertTrue($this->installedWorkshopRepo->hasWorkshop('learn-you-php'));
        $this->assertFileExists(sprintf('%s/workshops/learn-you-php', $this->workshopHomeDir));
    }

    public function testWorkshopTempDownloadIsRemovedIfExists(): void
    {
        $workshop = $this->configureRemoteRepository();
        $this->configureGitHubApi($workshop, true);

        mkdir(sprintf('%s/.temp', $this->workshopHomeDir), 0775, true);
        touch(sprintf('%s/.temp/learn-you-php.zip', $this->workshopHomeDir));

        $this->composerInstaller
            ->expects($this->once())
            ->method('install')
            ->with(sprintf('%s/workshops/learn-you-php', $this->workshopHomeDir))
            ->willReturn(new InstallResult(0, ''));

        $this->localJsonFile
            ->expects($this->once())
            ->method('write');

        $this->linker
            ->expects($this->once())
            ->method('link')
            ->with($this->isInstanceOf(InstalledWorkshop::class));

        $this->installer->installWorkshop($workshop->getCode());

        $this->assertTrue($this->installedWorkshopRepo->hasWorkshop('learn-you-php'));
        $this->assertFileExists(sprintf('%s/workshops/learn-you-php', $this->workshopHomeDir));
    }

    private function configureRemoteRepository(): Workshop
    {
        $this->remoteWorkshopRepo
            ->expects($this->once())
            ->method('hasWorkshop')
            ->with('learn-you-php')
            ->willReturn(true);

        $workshop = new Workshop('learn-you-php', 'learnyouphp', 'aydin', 'repo', 'workshop', 'core');
        $this->remoteWorkshopRepo
            ->expects($this->once())
            ->method('getByCode')
            ->with('learn-you-php')
            ->willReturn($workshop);

        return $workshop;
    }

    private function configureGitHubApi(Workshop $workshop, bool $configureDownload): void
    {
        $gitData = $this->createMock(GitData::class);
        $tags = $this->createMock(Tags::class);

        if ($configureDownload) {
            $repo = $this->createMock(Repo::class);
            $repo
                ->method('contents')
                ->willReturn($this->getContentsStub($workshop));

            $this->ghClient
                ->expects($this->exactly(2))
                ->method('api')
                ->withConsecutive(['git'], ['repo'])
                ->willReturnOnConsecutiveCalls($gitData, $repo);
        } else {
            $this->ghClient
                ->expects($this->once())
                ->method('api')
                ->with('git')
                ->willReturn($gitData);
        }

        $gitData
            ->method('tags')
            ->willReturn($tags);

        $tags
            ->expects($this->once())
            ->method('all')
            ->with($workshop->getGitHubOwner(), $workshop->getGitHubRepoName())
            ->willReturn([
                [
                    'ref' => 'refs/tags/1.0.0',
                    'object' => ['sha' => '0123456789']
                 ]
            ]);
    }

    private function createZipArchive(): void
    {
        $zipArchive = new ZipArchive;
        $zipArchive->open(sprintf('%s/temp.zip', $this->workshopHomeDir), ZipArchive::CREATE);
        $zipArchive->addEmptyDir('learnyouphp');
        $zipArchive->addFromString('learnyouphp/file1.txt', 'data');
        $zipArchive->addFromString('learnyouphp/composer.json', '{"name" : "learnyouphp"}');
        $zipArchive->close();
    }

    private function getContentsStub(Workshop $workshop): MockObject
    {
        $this->createZipArchive();

        $contents = $this->createMock(Contents::class);
        $contents
            ->expects($this->once())
            ->method('archive')
            ->with($workshop->getGitHubOwner(), $workshop->getGitHubRepoName(), 'zipball', '0123456789')
            ->willReturn(file_get_contents(sprintf('%s/temp.zip', $this->workshopHomeDir)));

        unlink(sprintf('%s/temp.zip', $this->workshopHomeDir));

        return $contents;
    }
}
