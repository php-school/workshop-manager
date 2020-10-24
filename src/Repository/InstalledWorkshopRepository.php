<?php

namespace PhpSchool\WorkshopManager\Repository;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;

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

    public function add(InstalledWorkshop $workshop): void
    {
        $this->workshops[$workshop->getCode()] = $workshop;
    }

    public function remove(InstalledWorkshop $workshopToRemove): void
    {
        if (!$this->hasWorkshop($workshopToRemove->getCode())) {
            throw new WorkshopNotFoundException();
        }

        unset($this->workshops[$workshopToRemove->getCode()]);
    }

    /**
     * @return array<InstalledWorkshop>
     */
    public function getAll(): array
    {
        return array_values($this->workshops);
    }

    public function getByCode(string $code): InstalledWorkshop
    {
        if (!$this->hasWorkshop($code)) {
            throw new WorkshopNotFoundException();
        }

        return $this->workshops[$code];
    }

    public function hasWorkshop(string $code): bool
    {
        return array_key_exists($code, $this->workshops);
    }

    public function isEmpty() : bool
    {
        return count($this->workshops) === 0;
    }

    public function save(): void
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
