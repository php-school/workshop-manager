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
     * @param Workshop[] $workshops
     */
    public function __construct(array $workshops = [])
    {
        array_walk($workshops, function (Workshop $workshop) {
           $this->addWorkshop($workshop);
        });
    }

    /**
     * @param WorkshopDataSource $dataSource
     * @return static
     */
    public function fromDataSource(WorkshopDataSource $dataSource)
    {
        return new static($dataSource->fetchWorkshops());
    }

    /**
     * @param Workshop $workshop
     */
    private function addWorkshop(Workshop $workshop)
    {
        $this->workshops[$workshop->getName()] = $workshop;
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
}
