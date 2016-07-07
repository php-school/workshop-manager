<?php

namespace PhpSchool\WorkshopManager;

use Github\Client;
use InvalidArgumentException;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use PhpSchool\WorkshopManager\Exception\FailedToMoveWorkshopException;
use PhpSchool\WorkshopManager\Exception\WorkshopAlreadyInstalledException;
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
     * @var WorkshopRepository
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
     * @param WorkshopRepository $installedWorkshops
     * @param Filesystem $filesystem
     * @param string $workshopHomeDirectory
     * @param ComposerInstallerFactory $composerFactory
     * @param Client $gitHubClient
     */
    public function __construct(
        WorkshopRepository $installedWorkshops,
        Filesystem $filesystem,
        $workshopHomeDirectory,
        ComposerInstallerFactory $composerFactory,
        Client $gitHubClient
    ) {
        $this->filesystem            = $filesystem;
        $this->workshopHomeDirectory = $workshopHomeDirectory;
        $this->composerFactory       = $composerFactory;
        $this->installedWorkshops    = $installedWorkshops;
        $this->gitHubClient          = $gitHubClient;
    }

    /**
     * @param Workshop $workshop
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

        $pathToZip  = $this->download($workshop);
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
    }

    /**
     * @param Workshop $workshop
     * @return string
     */
    private function download(Workshop $workshop)
    {
        $path = sprintf('%s/.temp/%s.zip', $this->workshopHomeDirectory, $workshop->getName());

        try {
            $tags = $this->gitHubClient->api('git')->tags()->all($workshop->getOwner(), $workshop->getRepo());
            $data = $this->gitHubClient->api('repo')->contents()->archive(
                $workshop->getOwner(),
                $workshop->getRepo(),
                'zipball',
                end($tags)['object']['sha']
            );
        } catch (InvalidArgumentException $e) {
            throw DownloadFailureException::fromException($e);
        }

        if ($this->filesystem->exists($path)) {
            try {
                $this->filesystem->remove($path);
            } catch (IOException $e) {
                throw DownloadFailureException::fromException($e);
            }
        }

        try {
            $this->filesystem->dumpFile($path, $data);
        } catch (IOException $e) {
            throw new DownloadFailureException('Failed to write zipball to filesystem');
        }

        return $path;
    }
}
