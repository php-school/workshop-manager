<?php

namespace PhpSchool\WorkshopManager\Exception;

/**
 * Class DownloadFailureException
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class DownloadFailureException extends \RuntimeException
{
    /**
     * @param \Exception $e
     * @return DownloadFailureException
     */
    public static function fromException(\Exception $e)
    {
        return new self($e->getMessage());
    }
}
