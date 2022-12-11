<?php

namespace PhpSchool\WorkshopManager\Repository;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\RequiresNetworkAccessException;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;

use function PhpSchool\WorkshopManager\collect;

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
     * @var array<Workshop>
     */
    private $workshops = [];

    public function __construct(JsonFile $remoteJsonFile)
    {
        $this->remoteJsonFile = $remoteJsonFile;
    }

    private function addWorkshop(Workshop $workshop): void
    {
        $this->workshops[$workshop->getCode()] = $workshop;
    }

    public function getByCode(string $code): Workshop
    {
        $this->init();
        if (!$this->hasWorkshop($code)) {
            throw new WorkshopNotFoundException();
        }

        return $this->workshops[$code];
    }

    public function hasWorkshop(string $code): bool
    {
        $this->init();
        return array_key_exists($code, $this->workshops);
    }

    /**
     * @return array<Workshop>
     */
    public function all(): array
    {
        $this->init();
        return $this->workshops;
    }

    /**
     * @return array<Workshop>
     */
    public function find(string $searchName): array
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
     */
    private function matchesWorkshop(Workshop $workshop, string $searchTerm): bool
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
     */
    private function matches(string $string, string $searchTerm): bool
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
    private function init(): void
    {
        if ($this->initialised) {
            return;
        }

        if (!checkdnsrr((string) parse_url($this->remoteJsonFile->getPath(), PHP_URL_HOST), 'A')) {
            throw new RequiresNetworkAccessException();
        }

        $requiredKeys = collect(
            ['workshop_code', 'display_name', 'github_owner', 'github_repo_name', 'description', 'type']
        );

        /** @var array{workshops: array<mixed>}> $workshops $data */
        $data = $this->remoteJsonFile->read();
        collect($data['workshops'])
            ->filter(function ($workshopData) use ($requiredKeys) {
                    $missingKeyCount = $requiredKeys
                        ->diff(array_map('strval', array_keys($workshopData)))
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
