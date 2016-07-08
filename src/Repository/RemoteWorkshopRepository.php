<?php

namespace PhpSchool\WorkshopManager\Repository;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\RequiresNetworkAccessException;

/**
 * @package PhpSchool\WorkshopManager\Repository
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class RemoteWorkshopRepository
{
    /**
     * flag to indicate remote repo has been loaded
     *
     * @var bool
     */
    private $initialised = false;

    /**
     * @var JsonFile
     */
    private $remoteJsonFile;

    /**
     * @var array
     */
    private $workshops = [];

    /**
     * @param JsonFile $remoteJsonFile
     */
    public function __construct(JsonFile $remoteJsonFile)
    {
        $this->remoteJsonFile = $remoteJsonFile;
    }

    /**
     * @param Workshop $workshop
     */
    private function addWorkshop(Workshop $workshop)
    {
        $this->workshops[$workshop->getName()] = $workshop;
    }

    /**
     * @param string $name
     *
     * @return Workshop
     * @throws WorkshopNotFoundException
     */
    public function getByName($name)
    {
        $this->init();
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
        $this->init();
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
        $this->init();
        $searchName = strtolower($searchName);

        return array_filter(
            $this->workshops,
            function (Workshop $workshop) use ($searchName) {
                return $this->matchesWorkshop($workshop, $searchName);
            }
        );
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
     * Load the remote data
     *
     * @throws RequiresNetworkAccessException
     */
    private function init()
    {
        if ($this->initialised) {
            return;
        }

        if (!checkdnsrr(parse_url($this->remoteJsonFile->getPath(), PHP_URL_HOST), 'A')) {
            throw new RequiresNetworkAccessException;
        }

        collect($this->remoteJsonFile->read()['workshops'])
            ->filter(
                function ($workshopData) {
                    $missingKeyCount = collect($workshopData)
                        ->keys()
                        ->diffKeys(['name', 'display_name', 'owner', 'repo', 'description'])
                        ->count();

                    //true if no missing keys
                    return $missingKeyCount === 0;
                }
            )
            ->map(
                function ($workshopData) {
                    return new Workshop(
                        $workshopData['name'],
                        $workshopData['display_name'],
                        $workshopData['owner'],
                        $workshopData['repo'],
                        $workshopData['description']
                    );
                }
            )
            ->each(function (Workshop $workshop) {
                $this->addWorkshop($workshop);
            });

        $this->initialised = true;
    }
}
