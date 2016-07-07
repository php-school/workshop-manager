<?php

namespace PhpSchool\WorkshopManager\Repository;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;

/**
 * Class InstalledWorkshopRepository
 * @package PhpSchool\WorkshopManager\Repository
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class InstalledWorkshopRepository implements WorkshopRepository
{
    /**
     * @var Workshop[]
     */
    protected $workshops = [];

    /**
     * @var JsonFile
     */
    private $file;

    /**
     * InstalledWorkshopRepository constructor.
     * @param JsonFile $file
     */
    public function __construct(JsonFile $file)
    {
        foreach ($this->deSerialise($file->read()['workshops']) as $workshop) {
            $this->addWorkshop($workshop);
        }
        $this->file = $file;
    }

    /**
     * @param Workshop $workshop
     */
    public function addWorkshop(Workshop $workshop)
    {
        $this->workshops[$workshop->getName()] = $workshop;
    }

    /**
     * @param Workshop $workshopToRemove
     * @throws WorkshopNotFoundException
     */
    public function removeWorkshop(Workshop $workshopToRemove)
    {
        if (!$this->hasWorkshop($workshopToRemove->getName())) {
            throw new WorkshopNotFoundException;
        }

        unset($this->workshops[$workshopToRemove->getName()]);
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return array_values($this->workshops);
    }

    /**
     * @param string $name
     *
     * @return Workshop
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
     * @param string $searchName
     *
     * @return Workshop[]
     * @throws WorkshopNotFoundException
     */
    public function find($searchName)
    {
        $searchName = strtolower($searchName);

        return array_filter(
            $this->workshops,
            function (Workshop $workshop) use ($searchName) {
                return $this->matchesWorkshop($workshop, $searchName);
            }
        );
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->workshops) === 0;
    }

    /**
     * Check if a workshop matches a search term.
     *
     * @param Workshop $workshop
     * @param string $searchTerm
     * @return bool
     */
    private function matchesWorkshop(Workshop $workshop, $searchTerm)
    {
        if ($this->matches($workshop->getName(), $searchTerm)) {
            return true;
        }

        if ($this->matches($workshop->getDisplayName(), $searchTerm)) {
            return true;
        }

        return false;
    }

    /**
     * Check if a string matches a search term.
     *
     * @param string $string
     * @param string $searchTerm
     * @return bool
     */
    private function matches($string, $searchTerm)
    {
        $string = strtolower($string);
        if (false !== strpos($string, $searchTerm)) {
            return true;
        }

        if (levenshtein($searchTerm, $string) <= 3) {
            return true;
        }

        return false;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function deSerialise(array $data)
    {
        return collect($data)
            ->filter(function ($workshopData) {
                $missingKeyCount = collect($workshopData)
                    ->keys()
                    ->diffKeys(['name', 'display_name', 'owner', 'repo', 'description'])
                    ->count();

                //true if no missing keys
                return $missingKeyCount === 0;
            })
            ->map(function ($workshopData) {
                return new Workshop(
                    $workshopData['name'],
                    $workshopData['display_name'],
                    $workshopData['owner'],
                    $workshopData['repo'],
                    $workshopData['description']
                );
            })
            ->all();
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        $state['workshops'] = array_map(function (Workshop $workshop) {
            return [
                'name' => $workshop->getName(),
                'display_name' => $workshop->getDisplayName(),
                'owner' => $workshop->getOwner(),
                'repo' => $workshop->getRepo(),
                'description' => $workshop->getDescription()
            ];
        }, $this->getAll());

        $this->file->write($state);
    }
}
