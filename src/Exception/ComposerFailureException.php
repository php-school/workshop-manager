<?php

namespace PhpSchool\WorkshopManager\Exception;

/**
 * Class ComposerFailureException
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
final class ComposerFailureException extends \RuntimeException
{
    /**
     * @param \Exception $e
     * @return ComposerFailureException
     */
    public static function fromException(\Exception $e)
    {
        return new self($e->getMessage());
    }
}
