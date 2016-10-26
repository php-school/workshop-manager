<?php

namespace PhpSchool\WorkshopManager\Exception;

/**
 * Class ComposerFailureException
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class ComposerFailureException extends \RuntimeException
{
    /**
     * @param \Exception $e
     * @return self
     */
    public static function fromException(\Exception $e)
    {
        return new self($e->getMessage());
    }

    /**
     * @param array $missingExtensions
     * @return self
     */
    public static function fromMissingExtensions(array $missingExtensions)
    {
        $message  = 'This workshop requires some extra PHP extensions. Please install them';
        $message .= ' and try again. Required extensions are %s.';

        return new self(sprintf($message, implode(', ', $missingExtensions)));
    }
}
