<?php

namespace PhpSchool\WorkshopManager;

use Github\Client;
use League\Flysystem\FileExistsException;
use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Entity\Workshop;

/**
 * Class Downloader
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class Downloader
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ManagerState
     */
    private $state;

    /**
     * @var bool
     */
    private $cleaned = false;

    /**
     * @param Client $client
     * @param Filesystem $filesystem
     * @param ManagerState $state
     */
    public function __construct(Client $client, Filesystem $filesystem, ManagerState $state)
    {
        $this->client     = $client;
        $this->filesystem = $filesystem;
        $this->state      = $state;
    }

    /**
     * @param Workshop $workshop
     *
     * @return string               Path to downloaded zipball
     * @throws FileExistsException  When unable to clean tempdir
     * @throws \RuntimeException    On write failure
     */
    public function download(Workshop $workshop)
    {
        $path = sprintf('.temp/%s.zip', $workshop->getName());
        $tags = $this->client->api('git')->tags()->all($workshop->getOwner(), $workshop->getRepo());
        $data = $this->client->api('repo')->contents()->archive(
            $workshop->getOwner(),
            $workshop->getRepo(),
            'zipball',
            end($tags)['object']['sha']
        );

        try {
            if (!$this->filesystem->write($path, $data)) {
                throw new \RuntimeException;
            }
        } catch (FileExistsException $e) {
            if ($this->cleaned) {
                throw $e;
            }

            $this->cleaned = true;
            $this->state->clearTemp();
            $this->download($workshop);
        }

        return $this->filesystem->getAdapter()->applyPathPrefix($path);
    }
}
