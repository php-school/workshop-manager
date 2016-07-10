<?php

namespace PhpSchool\WorkshopManager;

use Github\Client;
use Github\Exception\ExceptionInterface;
use InvalidArgumentException;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use PhpSchool\WorkshopManager\Exception\FailedToMoveWorkshopException;
use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
final class Installer
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ComposerInstallerFactory
     */
    private $composerFactory;

    /**
     * @var InstalledWorkshopRepository
     */
    private $installedWorkshops;

    /**
     * @var string
     */
    private $workshopHomeDirectory;

    /**
     * @var Client
     */
    private $gitHubClient;

    /**
     * @param InstalledWorkshopRepository $installedWorkshops
     * @param Filesystem $filesystem
     * @param string $workshopHomeDirectory
     * @param ComposerInstallerFactory $composerFactory
     * @param Client $gitHubClient
     */
    public function __construct(
        InstalledWorkshopRepository $installedWorkshops,
        Filesystem $filesystem,
        $workshopHomeDirectory,
        ComposerInstallerFactory $composerFactory,
        Client $gitHubClient
    ) {
        $this->installedWorkshops    = $installedWorkshops;
        $this->filesystem            = $filesystem;
        $this->workshopHomeDirectory = $workshopHomeDirectory;
        $this->composerFactory       = $composerFactory;
        $this->gitHubClient          = $gitHubClient;
    }

    /**
     * @param Workshop $workshop
     * @return string $version The version number of the workshop that was downloaded
     *
     * @throws WorkshopAlreadyInstalledException
     * @throws ComposerFailureException
     * @throws DownloadFailureException
     * @throws FailedToMoveWorkshopException
     */
    public function installWorkshop(Workshop $workshop)
    {
        if ($this->installedWorkshops->hasWorkshop($workshop->getName())) {
            throw new WorkshopAlreadyInstalledException;
        }

        list($version, $sha) = $this->getLatestVersionData($workshop);

        $pathToZip  = $this->download($workshop, $sha);
        $zipArchive = new \ZipArchive();

        $zipArchive->open($pathToZip);
        $zipArchive->extractTo(dirname($pathToZip));

        $sourcePath  = sprintf('%s/.temp/%s', $this->workshopHomeDirectory, $zipArchive->getNameIndex(0));
        $destinationPath = sprintf('%s/workshops/%s', $this->workshopHomeDirectory, $workshop->getName());

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

        try {
            $this->filesystem->executeInPath($destinationPath, function ($path) {
                $this->composerFactory->create($path)->run();
            });
        } catch (\Exception $e) {
            throw ComposerFailureException::fromException($e);
        }

        return $version;
    }

    /**
     * @param Workshop $workshop
     * @return array First element the version tag, eg 1.0.0, second the commit hash.
     */
    private function getLatestVersionData(Workshop $workshop)
    {
        try {
            $tags = $this->gitHubClient->api('git')->tags()->all($workshop->getOwner(), $workshop->getRepo());
        } catch (ExceptionInterface $e) {
            throw DownloadFailureException::fromException($e);
        }
        $tag  = end($tags);
        return [
            substr($tag['ref'], 10), //strip of refs/tags/
            $tag['object']['sha'],
        ];
    }

    /**
     * @param Workshop $workshop
     * @param string $sha The commit hash to download as an archive
     * @return string
     */
    private function download(Workshop $workshop, $sha)
    {
        $path = sprintf('%s/.temp/%s.zip', $this->workshopHomeDirectory, $workshop->getName());

        if ($this->filesystem->exists($path)) {
            try {
                $this->filesystem->remove($path);
            } catch (IOException $e) {
                throw DownloadFailureException::fromException($e);
            }
        }

        try {
            $data = $this->gitHubClient->api('repo')->contents()->archive(
                $workshop->getOwner(),
                $workshop->getRepo(),
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
