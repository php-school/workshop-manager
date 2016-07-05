<?php

namespace PhpSchool\WorkshopManager;

use Composer\Json\JsonFile;
use League\Flysystem\Filesystem;
use League\Flysystem\RootViolationException;
use PhpSchool\WorkshopManager\Entity\Workshop;

/**
 * Class ManagerState
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
final class ManagerState
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var JsonFile
     */
    private $stateFile;

    /**
     * @var Workshop[]
     */
    private $workshopsToAdd = [];

    /**
     * @var Workshop[]
     */
    private $workshopsToRemove = [];

    /**
     * @param Filesystem $filesystem
     * @param JsonFile $stateFile
     */
    public function __construct(
        Filesystem $filesystem,
        JsonFile $stateFile
    ) {
        $this->filesystem = $filesystem;
        $this->stateFile  = $stateFile;
    }

    public function addWorkshop(Workshop $workshop)
    {
        $this->workshopsToAdd[$workshop->getName()] = $workshop;
    }

    public function removeWorkshop(Workshop $workshop)
    {
        $this->workshopsToRemove[$workshop->getName()] = $workshop;
    }

    public function writeState()
    {
        // Read to array.
        $state = $this->readState();

        if (!array_key_exists('workshops', $state)) {
            $state['workshops'] = [];
        }

        foreach ($state['workshops'] as $key => $workshop) {
            // TODO: Maybe remove if it's in the to add list too?
            if (array_key_exists($workshop['name'], $this->workshopsToRemove)) {
                unset($state['workshops'][$key]);
            }
        }

        foreach ($this->workshopsToAdd as $workshop) {
            $state['workshops'][] = [
                'name'         => $workshop->getName(),
                'display_name' => $workshop->getDisplayName(),
                'owner'        => $workshop->getOwner(),
                'repo'         => $workshop->getRepo(),
                'description'  => $workshop->getDescription()
            ];
        }

        $this->stateFile->write($state);
    }

    /**
     * @return array
     */
    private function readState()
    {
        if ($this->stateFile->exists()) {
            return $this->stateFile->read();
        }

        return [];
    }

    /**
     * @return bool
     *
     * @throws RootViolationException In non existant circumstances
     */
    public function clearTemp()
    {
        return $this->filesystem->deleteDir('.temp');
    }
}
