<?php

namespace PhpSchool\WorkshopManager\Exception;

final class DownloadFailureException extends \RuntimeException
{
    public static function fromException(\Throwable $e): self
    {
        return new self($e->getMessage());
    }
}
