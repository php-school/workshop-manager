<?php

namespace PhpSchool\WorkshopManager\Exception;

/**
 * Class FailedToMoveWorkshopException
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class FailedToMoveWorkshopException extends \RuntimeException
{
    /**
     * @var string
     */
    private $srcPath;

    /**
     * @var string
     */
    private $destPath;

    /**
     * @param string $srcPath
     * @param string $destPath
     */
    public function __construct($srcPath, $destPath)
    {
        $this->srcPath  = $srcPath;
        $this->destPath = $destPath;

        parent::__construct(sprintf('Failed to move workshop files from "%s" to "%s"', $srcPath, $destPath));
    }

    /**
     * @return string
     */
    public function getDestPath()
    {
        return $this->destPath;
    }

    /**
     * @return string
     */
    public function getSrcPath()
    {
        return $this->srcPath;
    }
}
