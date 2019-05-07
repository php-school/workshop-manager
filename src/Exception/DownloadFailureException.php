<?php

namespace PhpSchool\WorkshopManager\Exception;

final class DownloadFailureException extends \RuntimeException
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
