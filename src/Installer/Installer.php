<?php

namespace PhpSchool\WorkshopManager\Installer;

use Exception;
use Github\Client;
use Github\Exception\ExceptionInterface;
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
use PhpSchool\WorkshopManager\Linker;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\RemoteWorkshopRepository;
use PhpSchool\WorkshopManager\VersionChecker;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
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

    /**
     * @param InstalledWorkshopRepository $installedWorkshops
     * @param RemoteWorkshopRepository $remoteWorkshopRepository
     * @param Linker $linker
     * @param Filesystem $filesystem
     * @param string $workshopHomeDirectory
     * @param ComposerInstaller $composerInstaller
     * @param Client $gitHubClient
     * @param VersionChecker $versionChecker
     * @param string|null $notifyUrlFormat
     */
    public function __construct(
        InstalledWorkshopRepository $installedWorkshops,
        RemoteWorkshopRepository $remoteWorkshopRepository,
        Linker $linker,
        Filesystem $filesystem,
        $workshopHomeDirectory,
        ComposerInstaller $composerInstaller,
        Client $gitHubClient,
        VersionChecker $versionChecker,
        $notifyUrlFormat = null
    ) {
        $this->installedWorkshopRepository  = $installedWorkshops;
        $this->remoteWorkshopRepository     = $remoteWorkshopRepository;
        $this->linker                       = $linker;
        $this->filesystem                   = $filesystem;
        $this->workshopHomeDirectory        = $workshopHomeDirectory;
        $this->composerInstaller            = $composerInstaller;
        $this->gitHubClient                 = $gitHubClient;
        $this->versionChecker               = $versionChecker;
        $this->notifyFormat                 = $notifyUrlFormat ?: $this->notifyFormat;
    }

    /**
     * @param string $workshop
     * @return string $version The version number of the workshop that was downloaded
     *
     * @throws WorkshopAlreadyInstalledException
     * @throws ComposerFailureException
     * @throws DownloadFailureException
     * @throws FailedToMoveWorkshopException
     */
    public function installWorkshop($workshop)
    {
        if ($this->installedWorkshopRepository->hasWorkshop($workshop)) {
            throw new WorkshopAlreadyInstalledException;
        }

        if (!$this->remoteWorkshopRepository->hasWorkshop($workshop)) {
            throw new WorkshopNotFoundException;
        }

        $workshop = $this->remoteWorkshopRepository->getByCode($workshop);

        try {
            $release = $this->versionChecker->getLatestRelease($workshop);
        } catch (RuntimeException $e) {
            throw DownloadFailureException::fromException($e);
        }

        $pathToZip  = $this->download($workshop, $release->getSha());
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
                
                throw new ComposerFailureException();
            }
        });

        $installedWorkshop = InstalledWorkshop::fromWorkshop($workshop, $release->getTag());
        $this->installedWorkshopRepository->add($installedWorkshop);
        $this->installedWorkshopRepository->save();

        $this->linker->link($installedWorkshop);

        $this->notifyInstall($installedWorkshop);
    }

    private function notifyInstall(InstalledWorkshop $workshop)
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

    /**
     * @param Workshop $workshop
     * @param string $sha The commit hash to download as an archive
     * @return string
     */
    private function download(Workshop $workshop, $sha)
    {
        $path = sprintf('%s/.temp/%s.zip', $this->workshopHomeDirectory, $workshop->getCode());

        if ($this->filesystem->exists($path)) {
            try {
                $this->filesystem->remove($path);
            } catch (IOException $e) {
                throw DownloadFailureException::fromException($e);
            }
        }

        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $data = $this->gitHubClient->api('repo')->contents()->archive(
                $workshop->getGitHubOwner(),
                $workshop->getGitHubRepoName(),
                'zipball',
                $sha
            );
        } catch (ExceptionInterface $e) {
            throw DownloadFailureException::fromException($e);
        }

        try {
            $this->filesystem->dumpFile($path, $data);
        } catch (IOException $e) {
            throw DownloadFailureException::fromException($e);
        }

        return $path;
    }
}
