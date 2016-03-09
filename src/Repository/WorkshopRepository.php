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
     * @var int[]
     */
    private $searchableWorkshops;

    /**
     * @param Workshop[] $workshops
     */
    public function __construct(array $workshops)
    {
        foreach ($workshops as $workshop) {
            if ($workshop instanceof Workshop) {
                $this->workshops[$workshop->getName()]                  = $workshop;
                $this->searchableWorkshops[$workshop->getName()]        = $workshop->getName();
                $this->searchableWorkshops[$workshop->getDisplayName()] = $workshop->getName();
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
}
