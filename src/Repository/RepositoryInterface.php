<?php

namespace PhpSchool\WorkshopManager\Repository;

/**
 * Interface RepositoryInterface
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
interface RepositoryInterface
{
    /**
     * @param $identifier
     * @return mixed
     */
    public function find($identifier);
}
