<?php

namespace PhpSchool\WorkshopManagerTest\Exception;

use Exception;
use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PHPUnit\Framework\TestCase;

class ComposerFailureExceptionTest extends TestCase
{
    public function testFromException(): void
    {
        $e = new Exception('Some Error');

        $composerException = ComposerFailureException::fromException($e);
        $this->assertEquals('Some Error', $composerException->getMessage());
    }

    public function testFromMissingExtensions(): void
    {
        $message  = 'This workshop requires some extra PHP extensions. Please install them';
        $message .= ' and try again. Required extensions are mbstring, zip.';

        $composerException = ComposerFailureException::fromMissingExtensions(['mbstring', 'zip']);
        $this->assertEquals($message, $composerException->getMessage());
    }

    public function testFromResolveError(): void
    {
        $message  = 'This workshops dependencies could not be resolved.';

        $composerException = ComposerFailureException::fromResolveError();
        $this->assertEquals($message, $composerException->getMessage());
    }
}
