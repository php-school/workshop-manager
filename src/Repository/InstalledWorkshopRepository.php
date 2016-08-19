<?php

namespace PhpSchool\WorkshopManager\Repository;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;

/**
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

        $requiredKeys = collect(
            ['workshop_code', 'display_name', 'github_owner', 'github_repo_name', 'description', 'type', 'version']
        );

        collect($file->read()['workshops'])
            ->filter(function ($workshopData) use ($requiredKeys) {
                $missingKeyCount = $requiredKeys
                    ->diff(array_keys($workshopData))
                    ->count();

                //true if no missing keys
                return $missingKeyCount === 0;
            })
            ->map(function ($workshopData) {
                    return new InstalledWorkshop(
                        $workshopData['workshop_code'],
                        $workshopData['display_name'],
                        $workshopData['github_owner'],
                        $workshopData['github_repo_name'],
                        $workshopData['description'],
                        $workshopData['type'],
                        $workshopData['version']
                    );
            })
            ->each(function (InstalledWorkshop $workshop) {
                $this->add($workshop);
            });
    }

    /**
     * @param InstalledWorkshop $workshop
     */
    public function add(InstalledWorkshop $workshop)
    {
        $this->workshops[$workshop->getCode()] = $workshop;
    }

    /**
     * @param InstalledWorkshop $workshopToRemove
     * @throws WorkshopNotFoundException
     */
    public function remove(InstalledWorkshop $workshopToRemove)
    {
        if (!$this->hasWorkshop($workshopToRemove->getCode())) {
            throw new WorkshopNotFoundException;
        }

        unset($this->workshops[$workshopToRemove->getCode()]);
    }

    /**
     * @return InstalledWorkshop[]
     */
    public function getAll()
    {
        return array_values($this->workshops);
    }

    /**
     * @param string $code
     *
     * @return InstalledWorkshop
     * @throws WorkshopNotFoundException
     */
    public function getByCode($code)
    {
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
        return array_key_exists($code, $this->workshops);
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
                'workshop_code' => $workshop->getCode(),
                'display_name' => $workshop->getDisplayName(),
                'github_owner' => $workshop->getGitHubOwner(),
                'github_repo_name' => $workshop->getGitHubRepoName(),
                'description' => $workshop->getDescription(),
                'type' => $workshop->getType(),
                'version' => $workshop->getVersion(),
            ];
        }, $this->getAll());

        $this->file->write($state);
    }
}
