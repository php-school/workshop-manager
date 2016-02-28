<?php

namespace PhpSchool\WorkshopManager\Repository;

use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;

/**
 * Class WorkshopRepository
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class WorkshopRepository implements RepositoryInterface
{
    /**
     * @var Workshop[]
     */
    private $workshops;

    /**
     * @param Workshop[] $workshops
     */
    public function __construct(array $workshops)
    {
        foreach ($workshops as $workshop) {
            if ($workshop instanceof Workshop) {
                $this->workshops[$workshop->getName()] = $workshop;
            }
        }
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
        $results = array_map(function ($name) {
            return $this->workshops[$name];
        }, array_filter(array_keys($this->workshops), function ($workshopName) use ($searchName) {
            return false !== strpos($workshopName, $searchName) || 3 >= levenshtein($searchName, $workshopName);
        }));

        if (!$results) {
            throw new WorkshopNotFoundException;
        }

        return $results;
    }
}
