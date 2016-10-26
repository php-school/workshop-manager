<?php

namespace PhpSchool\WorkshopManagerTest\Exception;

use Exception;
use PhpSchool\WorkshopManager\Exception\ComposerFailureException;
use PHPUnit_Framework_TestCase;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ComposerFailureExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testFromException()
    {
        $e = new Exception('Some Error');

        $composerException = ComposerFailureException::fromException($e);
        $this->assertEquals('Some Error', $composerException->getMessage());
    }

    public function testFromMissingExtensions()
    {
        $message  = 'This workshop requires some extra PHP extensions. Please install them';
        $message .= ' and try again. Required extensions are mbstring, zip.';

        $composerException = ComposerFailureException::fromMissingExtensions(['mbstring', 'zip']);
        $this->assertEquals($message, $composerException->getMessage());
    }
}
