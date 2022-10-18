<?php

namespace PhpSchool\WorkshopManager\Exception;

class ComposerFailureException extends \RuntimeException
{
    public static function fromException(\Exception $e): self
    {
        return new self($e->getMessage());
    }

    /**
     * @param array<string> $missingExtensions
     */
    public static function fromMissingExtensions(array $missingExtensions): self
    {
        $message  = 'This workshop requires some extra PHP extensions. Please install them';
        $message .= ' and try again. Required extensions are %s.';

        return new self(sprintf($message, implode(', ', $missingExtensions)));
    }

    public static function fromResolveError(): self
    {
        return new self('This workshops dependencies could not be resolved.');
    }
}
