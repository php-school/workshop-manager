<?php

namespace PhpSchool\WorkshopManager\Repository;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;

/**
 * @package PhpSchool\WorkshopManager\Repository
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class InstalledWorkshopRepository
{
    /**
     * @var InstalledWorkshop[]
     */
    private $workshops = [];

    /**
     * @var JsonFile
     */
    private $file;

    /**
     * @param JsonFile $file
     */
    public function __construct(JsonFile $file)
    {
        $this->file = $file;
        collect($file->read()['workshops'])
            ->filter(
                function ($workshopData) {
                    $missingKeyCount = collect($workshopData)
                        ->keys()
                        ->diff(['name', 'display_name', 'owner', 'repo', 'description', 'version'])
                        ->count();

                    //true if no missing keys
                    return $missingKeyCount === 0;
                }
            )
            ->map(
                function ($workshopData) {
                    return new InstalledWorkshop(
                        $workshopData['name'],
                        $workshopData['display_name'],
                        $workshopData['owner'],
                        $workshopData['repo'],
                        $workshopData['description'],
                        $workshopData['version']
                    );
                }
            )
            ->each(function (InstalledWorkshop $workshop) {
                $this->add($workshop);
            });
    }

    /**
     * @param InstalledWorkshop $workshop
     */
    public function add(InstalledWorkshop $workshop)
    {
        $this->workshops[$workshop->getName()] = $workshop;
    }

    /**
     * @param Workshop $workshopToRemove
     * @throws WorkshopNotFoundException
     */
    public function remove(Workshop $workshopToRemove)
    {
        if (!$this->hasWorkshop($workshopToRemove->getName())) {
            throw new WorkshopNotFoundException;
        }

        unset($this->workshops[$workshopToRemove->getName()]);
    }

    /**
     * @return InstalledWorkshop[]
     */
    public function getAll()
    {
        return array_values($this->workshops);
    }

    /**
     * @param string $name
     *
     * @return InstalledWorkshop
     * @throws WorkshopNotFoundException
     */
    public function getByName($name)
    {
        if (!$this->hasWorkshop($name)) {
            throw new WorkshopNotFoundException;
        }

        return $this->workshops[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasWorkshop($name)
    {
        return array_key_exists($name, $this->workshops);
    }



    /**
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->workshops) === 0;
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        $state['workshops'] = array_map(function (InstalledWorkshop $workshop) {
            return [
                'name' => $workshop->getName(),
                'display_name' => $workshop->getDisplayName(),
                'owner' => $workshop->getOwner(),
                'repo' => $workshop->getRepo(),
                'description' => $workshop->getDescription(),
                'version' => $workshop->getVersion(),
            ];
        }, $this->getAll());

        $this->file->write($state);
    }
}
