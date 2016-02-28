<?php

namespace PhpSchool\WorkshopManager;

use League\Flysystem\Filesystem;
use PhpSchool\WorkshopManager\Entity\Workshop;

/**
 * Class WorkshopInstaller
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class WorkshopInstaller
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function install(Workshop $workshop)
    {
        // TODO: Implement
    }
}
