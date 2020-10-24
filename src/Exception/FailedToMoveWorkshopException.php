<?php

namespace PhpSchool\WorkshopManager\Exception;

final class FailedToMoveWorkshopException extends \RuntimeException
{
    /**
     * @var string
     */
    private $srcPath;

    /**
     * @var string
     */
    private $destPath;

    public function __construct(string $srcPath, string $destPath)
    {
        $this->srcPath = $srcPath;
        $this->destPath = $destPath;

        parent::__construct(sprintf('Failed to move workshop files from "%s" to "%s"', $srcPath, $destPath));
    }

    public function getDestPath(): string
    {
        return $this->destPath;
    }

    public function getSrcPath(): string
    {
        return $this->srcPath;
    }
}
