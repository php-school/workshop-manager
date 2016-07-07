<?php

namespace PhpSchool\WorkshopManager\Repository;

use PhpSchool\WorkshopManager\Entity\Workshop;

/**
 * @package PhpSchool\WorkshopManager\Repository
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
interface WorkshopRepository
{
    /**
     * @param Workshop $workshop
     */
    public function addWorkshop(Workshop $workshop);

    /**
     * @return array
     */
    public function getAll();

    /**
     * @param string $name
     *
     * @return Workshop
     * @throws WorkshopNotFoundException
     */
    public function getByName($name);

    /**
     * @param string $name
     * @return bool
     */
    public function hasWorkshop($name);

    /**
     * @param string $searchName
     *
     * @return Workshop[]
     */
    public function find($searchName);

    /**
     * @return bool
     */
    public function isEmpty();
}
