<?php

namespace PhpSchool\WorkshopManager\Repository;

use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\WorkshopDataSource;

/**
 * Class WorkshopRepository
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class WorkshopRepository implements RepositoryInterface
{
    /**
     * @var Workshop[]
     */
    private $workshops = [];

    /**
     * @var int[]
     */
    private $searchableWorkshops = [];

    /**
     * @param WorkshopDataSource $workshopSrc
     */
    public function __construct(WorkshopDataSource $workshopSrc)
    {
        $this->workshops = $workshopSrc->fetchWorkshops();
        
        foreach ($this->workshops as $workshop) {
            $this->workshops[$workshop->getName()]                  = $workshop;
            $this->searchableWorkshops[$workshop->getName()]        = $workshop->getName();
            $this->searchableWorkshops[$workshop->getDisplayName()] = $workshop->getName();
        }
    }

    /**
     * @return Workshop[]
     */
    public function getAllWorkshops()
    {
        return $this->workshops;
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
        $results = array_map(function ($workshopKey) {
            return $this->workshops[$workshopKey];
        }, array_unique(array_filter($this->searchableWorkshops, function ($key, $searchable) use ($searchName) {
            $searchable = strtolower($searchable);
            $searchName = strtolower($searchName);
            return false !== stripos($searchable, $searchName) || 3 >= levenshtein($searchName, $searchable);
        }, ARRAY_FILTER_USE_BOTH)));

        if (!$results) {
            throw new WorkshopNotFoundException;
        }

        return $results;
    }

    /**
     * @return bool
     */
    public function isempty()
    {
        return count($this->workshops) === 0;
    }
}
