<?php

namespace PhpSchool\WorkshopManager;

use Composer\Json\JsonFile;
use League\Flysystem\FileNotFoundException;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\RequiresNetworkAccessException;

/**
 * Class WorkshopDataSource
 * @author Michael Woodward <michael@wearejh.com>
 */
final class WorkshopDataSource
{
    /**
     * @var string
     */
    private $src;

    /**
     * @var null|array
     */
    private $data;

    /**
     * @param string $src
     */
    private function __construct($src)
    {
        $this->src = $src;
    }

    /**
     * @param JsonFile $file
     * @return WorkshopDataSource
     * @throws FileNotFoundException
     */
    public static function createFromLocalPath(JsonFile $file)
    {
        if (!$file->exists()) {
            throw new FileNotFoundException($file->getPath());
        }

        return new self($file->getPath());
    }

    /**
     * @param string $src
     * @return WorkshopDataSource
     * @throws RequiresNetworkAccessException
     */
    public static function createFromExternalSrc($src)
    {
        if (checkdnsrr($src, 'A')) {
            throw new RequiresNetworkAccessException;
        }

        return new self($src);
    }

    /**
     * @return Workshop[]
     */
    public function fetchWorkshops()
    {
        return array_map(function ($workshop) {
            return new Workshop(
                $workshop['name'],
                $workshop['display_name'],
                $workshop['owner'],
                $workshop['repo'],
                $workshop['description']
            );
        }, array_filter($this->fetch(), function ($workshop) {
            $diff = array_diff(
                ['name', 'display_name', 'owner', 'repo', 'description'],
                array_keys($workshop)
            );

            return count($diff) === 0;
        }));
    }
    
    /**
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->fetch()) === 0;
    }
    
    /**
     * @return array
     */
    private function fetch()
    {
        if (null === $this->data) {
            $this->data = json_decode(file_get_contents($this->src, true));
        }

        return $this->data;
    }
}
