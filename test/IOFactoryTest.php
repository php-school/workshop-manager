<?php

namespace PhpSchool\WorkshopManagerTest;

use Composer\IO\ConsoleIO;
use Composer\IO\NullIO;
use PhpSchool\WorkshopManager\IOFactory;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class IOFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }

//    public function testGetIO()
//    {
//        $factory = new IOFactory;
//        $input = $this->createMock(InputInterface::class);
//        $output = $this->createMock(OutputInterface::class);
//
//        $this->assertInstanceOf(ConsoleIO::class, $factory->getIO($input, $output));
//    }
//
//    public function testGetNullableIOReturnsNullIOIfNotInVerboseMode()
//    {
//        $factory = new IOFactory;
//        $input = $this->createMock(InputInterface::class);
//        $output = $this->createMock(OutputInterface::class);
//
//        $output
//            ->expects($this->once())
//            ->method('getVerbosity')
//            ->willReturn(OutputInterface::VERBOSITY_NORMAL);
//
//        $this->assertInstanceOf(NullIO::class, $factory->getNullableIO($input, $output));
//    }
//
//    public function testGetNullableIOReturnsNormalIOIfInVerboseMode()
//    {
//        $factory = new IOFactory;
//        $input = $this->createMock(InputInterface::class);
//        $output = $this->createMock(OutputInterface::class);
//
//        $output
//            ->expects($this->once())
//            ->method('getVerbosity')
//            ->willReturn(OutputInterface::VERBOSITY_VERBOSE);
//
//        $this->assertInstanceOf(ConsoleIO::class, $factory->getNullableIO($input, $output));
//    }
}
