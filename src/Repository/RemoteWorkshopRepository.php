<?php

namespace PhpSchool\WorkshopManager\Repository;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\RequiresNetworkAccessException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;

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
        $this->workshops[$workshop->getCode()] = $workshop;
    }

    /**
     * @param string $code
     *
     * @return Workshop
     * @throws WorkshopNotFoundException
     */
    public function getByCode($code)
    {
        $this->init();
        if (!$this->hasWorkshop($code)) {
            throw new WorkshopNotFoundException;
        }

        return $this->workshops[$code];
    }

    /**
     * @param string $code
     * @return bool
     */
    public function hasWorkshop($code)
    {
        $this->init();
        return array_key_exists($code, $this->workshops);
    }

    /**
     * @return array
     */
    public function all()
    {
        $this->init();
        return $this->workshops;
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
        if ($this->matches($workshop->getCode(), $searchTerm)) {
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

        $requiredKeys = collect(
            ['workshop_code', 'display_name', 'github_owner', 'github_repo_name', 'description', 'type']
        );

        collect($this->remoteJsonFile->read()['workshops'])
            ->filter(function ($workshopData) use ($requiredKeys) {
                    $missingKeyCount = $requiredKeys
                        ->diff(array_keys($workshopData))
                        ->count();

                    //true if no missing keys
                    return $missingKeyCount === 0;
            })
            ->map(function ($workshopData) {
                    return new Workshop(
                        $workshopData['workshop_code'],
                        $workshopData['display_name'],
                        $workshopData['github_owner'],
                        $workshopData['github_repo_name'],
                        $workshopData['description'],
                        $workshopData['type']
                    );
            })
            ->each(function (Workshop $workshop) {
                $this->addWorkshop($workshop);
            });

        $this->initialised = true;
    }
}
