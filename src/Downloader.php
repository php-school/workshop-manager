<?php

namespace PhpSchool\WorkshopManager;

use Github\Client;
use Github\Exception\ExceptionInterface;
use PhpSchool\WorkshopManager\Entity\Release;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\DownloadFailureException;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class Downloader
{
    private $workshopHomeDirectory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Client
     */
    private $gitHubClient;

    public function __construct(Filesystem $filesystem, Client $gitHubClient, $workshopHomeDirectory)
    {
        $this->workshopHomeDirectory = $workshopHomeDirectory;
        $this->filesystem = $filesystem;
        $this->gitHubClient = $gitHubClient;
    }

    public function download(Workshop $workshop, Release $release)
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
            /** @noinspection PhpUndefinedMethodInspection */
            $data = $this->gitHubClient->api('repo')->contents()->archive(
                $workshop->getOwner(),
                $workshop->getRepo(),
                'zipball',
                $release->getSha()
            );
        } catch (ExceptionInterface $e) {
            throw DownloadFailureException::fromException($e);
        }

        try {
            $this->filesystem->dumpFile($path, $data);
        } catch (IOException $e) {
            throw DownloadFailureException::fromException($e);
        }

        $zipArchive = new \ZipArchive();

        $zipArchive->open($path);
        $zipArchive->extractTo(dirname($path));

        $zipFolderName = $zipArchive->getNameIndex(0);
        $zipArchive->close();

        $this->filesystem->remove($path);

        return sprintf('%s/.temp/%s', $this->workshopHomeDirectory, $zipFolderName);
    }

}
