<?php

namespace PhpSchool\WorkshopManager\Installer;

use Exception;
use PhpSchool\WorkshopManager\Entity\Branch;
use PhpSchool\WorkshopManager\Entity\Release;
use PhpSchool\WorkshopManager\GitHubApi\Client;
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
use PhpSchool\WorkshopManager\GitHubApi\Exception as GitHubException;
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\RemoteWorkshopRepository;
use PhpSchool\WorkshopManager\VersionChecker;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;

class Installer
{
    /**
     * @var string
     */
    private $notifyFormat = "https://www.phpschool.io/downloads/%s/%s";

    /**
     * @var InstalledWorkshopRepository
     */
    private $installedWorkshopRepository;

    /**
     * @var RemoteWorkshopRepository
     */
    private $remoteWorkshopRepository;

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
     * @var Client
     */
    private $gitHubClient;

    /**
     * @var VersionChecker
     */
    private $versionChecker;

    /**
     * @var ComposerInstaller
     */
    private $composerInstaller;

    public function __construct(
        InstalledWorkshopRepository $installedWorkshops,
        RemoteWorkshopRepository $remoteWorkshopRepository,
        Linker $linker,
        Filesystem $filesystem,
        string $workshopHomeDirectory,
        ComposerInstaller $composerInstaller,
        Client $gitHubClient,
        VersionChecker $versionChecker,
        string $notifyUrlFormat = null
    ) {
        $this->installedWorkshopRepository = $installedWorkshops;
        $this->remoteWorkshopRepository = $remoteWorkshopRepository;
        $this->linker = $linker;
        $this->filesystem = $filesystem;
        $this->workshopHomeDirectory = $workshopHomeDirectory;
        $this->composerInstaller = $composerInstaller;
        $this->gitHubClient = $gitHubClient;
        $this->versionChecker = $versionChecker;
        $this->notifyFormat = $notifyUrlFormat ?: $this->notifyFormat;
    }

    public function installWorkshop(string $workshop, Branch $branch = null): void
    {
        if ($this->installedWorkshopRepository->hasWorkshop($workshop)) {
            throw new WorkshopAlreadyInstalledException();
        }

        if (!$this->remoteWorkshopRepository->hasWorkshop($workshop)) {
            throw new WorkshopNotFoundException();
        }

        $workshop = $this->remoteWorkshopRepository->getByCode($workshop);

        $pathToZip = $this->getPathAndCreateDirectory($workshop);

        $data = $branch
            ? $this->downloadBranch($workshop, $branch)
            : $this->downloadRelease($workshop, $release = $this->getLatestRelease($workshop));

        $this->writeWorkshop($pathToZip, $data);

        $zipArchive = new \ZipArchive();

        $zipArchive->open($pathToZip);
        $zipArchive->extractTo(dirname($pathToZip));

        $sourcePath  = sprintf('%s/.temp/%s', $this->workshopHomeDirectory, $zipArchive->getNameIndex(0));
        $destinationPath = sprintf('%s/workshops/%s', $this->workshopHomeDirectory, $workshop->getCode());

        $zipArchive->close();
        $this->filesystem->remove($pathToZip);

        //if destination exists we can just remove it as it's not installed
        //according to repo
        if ($this->filesystem->exists($destinationPath)) {
            $this->filesystem->remove($destinationPath);
        }

        //ensure workshops dir exists
        if (!$this->filesystem->exists(sprintf('%s/workshops', $this->workshopHomeDirectory))) {
            $this->filesystem->mkdir(sprintf('%s/workshops', $this->workshopHomeDirectory));
        }

        try {
            $this->filesystem->rename($sourcePath, $destinationPath);
        } catch (IOException $e) {
            throw new FailedToMoveWorkshopException($sourcePath, $destinationPath);
        }

        $this->filesystem->executeInPath($destinationPath, function ($path) {
            try {
                $result = $this->composerInstaller->install($path);
            } catch (Exception $e) {
                throw ComposerFailureException::fromException($e);
            }

            if ($result->getExitCode() > 0) {
                if ($result->missingExtensions()) {
                    throw ComposerFailureException::fromMissingExtensions($result->getMissingExtensions());
                }

                if ($result->couldNotBeResolved()) {
                    throw ComposerFailureException::fromResolveError();
                }

                throw new ComposerFailureException($result->getOutput());
            }
        });

        $installedWorkshop = InstalledWorkshop::fromWorkshop(
            $workshop,
            $branch ? (string) $branch : $release->getTag()
        );
        $this->installedWorkshopRepository->add($installedWorkshop);
        $this->installedWorkshopRepository->save();

        $this->linker->link($installedWorkshop);

        $this->notifyInstall($installedWorkshop);
    }

    private function notifyInstall(InstalledWorkshop $workshop): void
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => sprintf($this->notifyFormat, $workshop->getCode(), $workshop->getVersion()),
                CURLOPT_POST => 1,
                CURLOPT_RETURNTRANSFER => 1,
            ]
        );
        curl_exec($curl);
        curl_close($curl);
    }

    private function getPathAndCreateDirectory(Workshop $workshop): string
    {
        $path = sprintf('%s/.temp/%s.zip', $this->workshopHomeDirectory, $workshop->getCode());

        if ($this->filesystem->exists($path)) {
            try {
                $this->filesystem->remove($path);
            } catch (IOException $e) {
                throw DownloadFailureException::fromException($e);
            }
        }

        return $path;
    }

    private function writeWorkshop(string $path, string $data): void
    {
        try {
            $this->filesystem->dumpFile($path, $data);
        } catch (IOException $e) {
            throw DownloadFailureException::fromException($e);
        }
    }

    private function downloadBranch(Workshop $workshop, Branch $branch): string
    {
        return $branch->isDifferentRepository()
            ? $this->downloadArchive($branch->getGitHubOwner(), $branch->getGitHubRepoName(), $branch->getBranch())
            : $this->downloadArchive($workshop->getGitHubOwner(), $workshop->getGitHubRepoName(), $branch->getBranch());
    }

    private function downloadRelease(Workshop $workshop, Release $release): string
    {
        return $this->downloadArchive($workshop->getGitHubOwner(), $workshop->getGitHubRepoName(), $release->getSha());
    }

    private function downloadArchive(string $owner, string $repo, string $reference): string
    {
        try {
            return $this->gitHubClient->archive(
                $owner,
                $repo,
                'zipball',
                $reference
            );
        } catch (GitHubException $e) {
            throw DownloadFailureException::fromException($e);
        }
    }

    private function getLatestRelease(Workshop $workshop): Release
    {
        try {
            return $this->versionChecker->getLatestRelease($workshop);
        } catch (RuntimeException $e) {
            throw DownloadFailureException::fromException($e);
        }
    }
}
