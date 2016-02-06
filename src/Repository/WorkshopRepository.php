<?php

namespace PhpSchool\WorkshopManager\Repository;

use InvalidArgumentException;
use PhpSchool\WorkshopManager\Entity\Workshop;

/**
 * Class WorkshopRepository
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class WorkshopRepository implements RepositoryInterface
{
    /**
     * @var string
     */
    private $source;

    /**
     * @var array
     */
    private $data;

    /**
     * Accepts path to JSON source
     *
     * @param string $source
     */
    public function __construct($source)
    {
        $this->source = $source;
        $this->data   = json_decode(file_get_contents($source), true);
    }
    
    /**
     * @param $name
     *
     * @return Workshop
     * @throws InvalidArgumentException
     */
    public function find($name)
    {
        $index = array_search($name, array_column($this->data['workshops'], 'name'), true);

        if (false === $index) {
            throw new InvalidArgumentException('No workshop with that name available!');
        }

        return new Workshop(
            $this->data['workshops'][$index]['name'],
            $this->data['workshops'][$index]['display_name'],
            $this->data['workshops'][$index]['owner'],
            $this->data['workshops'][$index]['repo'],
            $this->data['workshops'][$index]['description']
        );
    }
}
